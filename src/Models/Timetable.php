<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Models;

use LogicException;

class Timetable {
    // this number must be greater than the maximum number of calls for a train
    private const MULTIPLIER = 1000;

    /**
     * @param Station[] $stations
     * @param ServiceCall[][] $calls
     */
    public function __construct(
        public readonly array $stations
        , public readonly array $calls
    ) {
    }

    public static function generateFromBoard(DepartureBoard $board) : static {
        $calls = $board->calls;
        // try to order the stations
        $stations = [];
        // I hope this is good enough - I don't know how to sort the stations properly
        usort(
            $calls
            , static fn(
                ServiceCallWithDestinationAndCalls $a
                , ServiceCallWithDestinationAndCalls $b
            ) => -(count($a->subsequentCalls) <=> count($b->subsequentCalls))
        );
        foreach ($calls as $call) {
            foreach (array_keys($call->destinations) as $portion) {
                $order = [];
                $i = 0;
                foreach ($call->subsequentCalls as $subsequent_call) {
                    if (array_key_exists($portion, $subsequent_call->destinations)) {
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
        $stations = array_merge([$calls[0]->call->location], $stations);

        $matrix = [];

        // fill the calls matrix
        $i = 0;
        foreach ($calls as $call) {
            foreach (array_keys($call->destinations) as $portion) {
                $matrix[0][$i] = $call;
                $j = 1;
                foreach ($call->subsequentCalls as $subsequent_call) {
                    $subsequent_crs = $subsequent_call->call->location->crsCode;
                    if ($subsequent_crs !== null && array_key_exists($portion, $subsequent_call->destinations)) {
                        while ($stations[$j]->crsCode !== $subsequent_crs) {
                            ++$j;
                            if (!isset($stations[$j])) {
                                throw new LogicException('Should not happen');
                            }
                        }
                        $matrix[$j][$i] = $subsequent_call;
                        ++$j;
                    }
                }
                ++$i;
            }
        }

        return new static($stations, $matrix);
    }
}