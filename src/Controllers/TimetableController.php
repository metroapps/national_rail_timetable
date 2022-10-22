<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Controllers;

use LogicException;
use Miklcct\NationalRailTimetable\Enums\TimeType;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\ServiceCallWithDestinationAndCalls;
use Miklcct\NationalRailTimetable\Models\Time;
use Miklcct\NationalRailTimetable\Repositories\LocationRepositoryInterface;
use Miklcct\NationalRailTimetable\Repositories\ServiceRepositoryFactoryInterface;
use Miklcct\NationalRailTimetable\Views\TimetableView;
use Miklcct\ThinPhpApp\Controller\Application;
use Miklcct\ThinPhpApp\Response\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Safe\DateTimeImmutable;
use Teapot\HttpException;
use Teapot\StatusCode\Http;

class TimetableController extends Application {
    // this number must be greater than the maximum number of calls for a train
    private const MULTIPLIER = 1000;

    public function __construct(
        private readonly ViewResponseFactoryInterface $viewResponseFactory
        , private readonly StreamFactoryInterface $streamFactory
        , private readonly ServiceRepositoryFactoryInterface $serviceRepositoryFactory
        , private readonly LocationRepositoryInterface $locationRepository
    ) {}

    public function run(ServerRequestInterface $request) : ResponseInterface {
        $query = $request->getQueryParams();

        $station = $this->locationRepository->getLocationByCrs($query['station'])
            ?? $this->locationRepository->getLocationByName($query['station']);
        if ($station === null) {
            throw new HttpException('The station cannot be found', Http::NOT_FOUND);
        }
        $date = Date::fromDateTimeInterface(new DateTimeImmutable($query['date'] ?: 'now'));
        $board = ($this->serviceRepositoryFactory)(false)->getDepartureBoard(
            $station->crsCode
            , $date->toDateTimeImmutable()
            , $date->toDateTimeImmutable(new Time(28, 30))
            , TimeType::PUBLIC_DEPARTURE
        );
        $filter = array_filter($query['filter'] ?? []);
        if (!empty($filter)) {
            $board = $board->filterByDestination($filter, true);
        }

        // group the stations and calls
        $station_group = [];
        $call_group = [];
        foreach ($board->calls as $call) {
            $group_id = $station_group === [] ? 0 : max($station_group) + 1;
            foreach ($call->subsequentCalls as $subsequent_call) {
                $subsequent_crs = $subsequent_call->call->location->crsCode;
                if (isset($station_group[$subsequent_crs])) {
                    $group_to_be_joined = $station_group[$subsequent_crs];
                    if ($group_to_be_joined !== $group_id) {
                        foreach ([&$station_group, &$call_group] as &$array) {
                            foreach ($array as &$g) {
                                if ($g === $group_id) {
                                    $g = $group_to_be_joined;
                                }
                            }
                            unset($g);
                        }
                        $group_id = $group_to_be_joined;
                        unset($array);
                    }
                } else {
                    $station_group[$subsequent_crs] = $group_id;
                }
            }
            $call_group[$call->uid . '_' . $call->date] = $group_id;
        }

        $timetables = [];

        foreach (array_unique($call_group) as $group_id) {
            // try to order the stations
            $stations = [];
            $calls = $board->calls;
            // I hope this is good enough - I don't know how to sort the stations properly
            usort(
                $calls
                , static fn(
                    ServiceCallWithDestinationAndCalls $a
                    , ServiceCallWithDestinationAndCalls $b
                ) => -(count($a->subsequentCalls) <=> count($b->subsequentCalls))
            );
            foreach ($calls as $call) {
                if ($call_group[$call->uid . '_' . $call->date] === $group_id) {
                    foreach (array_keys($call->destinations) as $portion) {
                        $order = [];
                        $i = 0;
                        foreach ($call->subsequentCalls as $subsequent_call) {
                            if (in_array($portion, array_keys($subsequent_call->destinations), true)) {
                                $current_station = $subsequent_call->call->location;
                                if ($current_station->crsCode !== null) {
                                    $found = null;
                                    $old_i = $i;
                                    while (isset($stations[$i])) {
                                        if ($stations[$i]->crsCode === $current_station->crsCode) {
                                            $found = $i++;
                                            break;
                                        }
                                        ++$i;
                                    }
                                    if (!$found) {
                                        $i = $old_i;
                                    }
                                    $order[] = [$current_station, $found === null ? null : $found * self::MULTIPLIER];
                                }
                            }
                        }
                        foreach ($order as $j => $item) {
                            if ($item[1] !== null) {
                                for ($k = $j - 1; $k >= 0 && $order[$k][1] === null; --$k) {
                                    $order[$k][1] = $item[1] - self::MULTIPLIER + 1 + $k;
                                }
                            }
                        }
                        $max = count($stations);
                        foreach ($order as &$item) {
                            if ($item[1] === null) {
                                $item[1] = $max++ * self::MULTIPLIER;
                            }
                        }
                        unset($item);

                        $new_stations = array_reduce(
                            $order
                            , static fn(array $carry, array $item) : array
                                => $carry + [$item[1] => $item[0]]
                            , array_combine(
                                array_map(
                                    static fn(int $x) => $x * self::MULTIPLIER
                                    , array_keys($stations)
                                )
                                , array_values($stations)
                            )
                        );
                        ksort($new_stations);
                        $stations = array_values($new_stations);
                    }
                }
            }
            $stations = array_merge([$calls[0]->call->location], $stations);

            $calls = [];

            // fill the calls matrix
            $i = 0;
            foreach ($board->calls as $call) {
                if ($call_group[$call->uid . '_' . $call->date] === $group_id) {
                    foreach (array_keys($call->destinations) as $portion) {
                        $calls[0][$i] = $call;
                        $j = 1;
                        foreach ($call->subsequentCalls as $subsequent_call) {
                            $subsequent_crs = $subsequent_call->call->location->crsCode;
                            if ($subsequent_crs !== null && in_array($portion, array_keys($subsequent_call->destinations), true)) {
                                while ($stations[$j]->crsCode !== $subsequent_crs) {
                                    ++$j;
                                    if (!isset($stations[$j])) {
                                        throw new LogicException('Should not happen');
                                    }
                                }
                                if ($j === 0) throw new LogicException('Should not happen');
                                $calls[$j][$i] = $subsequent_call;
                                ++$j;
                            }
                        }
                        ++$i;
                    }
                }
            }

            $timetables[$group_id] = ['stations' => $stations, 'calls' => $calls];

        }

        return ($this->viewResponseFactory)(new TimetableView($this->streamFactory, $station, $date, $timetables, $filter));

    }
}