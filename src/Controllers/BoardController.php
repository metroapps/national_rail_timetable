<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Controllers;

use DateTimeImmutable;
use DateInterval;
use Miklcct\NationalRailTimetable\Models\Station;
use Miklcct\NationalRailTimetable\Enums\TimeType;
use Miklcct\NationalRailTimetable\Views\BoardFormView;
use Miklcct\ThinPhpApp\Controller\Application;
use Miklcct\ThinPhpApp\Response\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Http\Factory\Guzzle\StreamFactory;
use InvalidArgumentException;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\Time;
use Miklcct\NationalRailTimetable\Repositories\FixedLinkRepositoryInterface;
use Miklcct\NationalRailTimetable\Repositories\LocationRepositoryInterface;
use Miklcct\NationalRailTimetable\Repositories\ServiceRepositoryFactoryInterface;
use Miklcct\NationalRailTimetable\Views\BoardView;

class BoardController extends Application {
    public function __construct(
        private readonly ViewResponseFactoryInterface $viewResponseFactory
        , private readonly LocationRepositoryInterface $locationRepository
        , private readonly ServiceRepositoryFactoryInterface $serviceRepositoryFactory
        , private readonly FixedLinkRepositoryInterface $fixedLinkRepository
    ) {}
    
    public function run(ServerRequestInterface $request) : ResponseInterface {
        $query = $request->getQueryParams();
        $self = $request->getServerParams()['PHP_SELF'];
        if (empty($query['station'])) {
            return ($this->viewResponseFactory)(
                new BoardFormView(
                    new StreamFactory()
                    , $self
                    , $this->locationRepository->getAllStations()
                )
            );
        }

        $station = $this->locationRepository->getLocationByCrs($query['station'])
            ?? $this->locationRepository->getLocationByName($query['station']);
        if ($station?->crsCode === null) {
            throw new InvalidArgumentException('The station cannot be found.');
        }

        $destination = null;
        if (!empty($query['filter'])) {
            $destination = $this->locationRepository->getLocationByCrs($query['filter'])
                ?? $this->locationRepository->getLocationByName($query['filter']);
            if ($destination?->crsCode === null) {
                throw new InvalidArgumentException('The destination cannot be found.');
            }
        }

        $arrival_mode = $query['mode'] === 'arrivals';
        $date = Date::fromDateTimeInterface(new DateTimeImmutable(empty($query['date']) ? 'now' : $query['date']));
        $from = $date->toDateTimeImmutable(new Time(0, 0));
        $to = $date->toDateTimeImmutable(new Time(28, 30));
        $connecting_time = !empty($_GET['connecting_time']) ? new DateTimeImmutable($_GET['connecting_time']) : null;
        $permanent_only = !empty($query['permanent_only']);
        $service_repository = ($this->serviceRepositoryFactory)($permanent_only);
        $board = $service_repository->getDepartureBoard(
            $station->crsCode
            , $from
            , $to
            , $arrival_mode ? TimeType::PUBLIC_ARRIVAL : TimeType::PUBLIC_DEPARTURE
        );
        if ($destination !== null) {
            $board = $board->filterByDestination($destination->crsCode);
        }

        /** @var FixedLink[] */
        $fixed_links = [];
        $fixed_link_departure_time 
            = isset($connecting_time) && $station instanceof Station 
                ? $arrival_mode 
                    ? $connecting_time->sub(new DateInterval(sprintf('PT%dM', $station->minimumConnectionTime))) 
                    : $connecting_time->add(new DateInterval(sprintf('PT%dM', $station->minimumConnectionTime))) 
                : $from;
        foreach ($this->fixedLinkRepository->get($station->crsCode, null) as $fixed_link) {
            $arrival_time = $fixed_link->getArrivalTime($fixed_link_departure_time, $arrival_mode);
            $existing = $fixed_links[$fixed_link->destination->crsCode] ?? null;
            if (
                ($destination === null || $destination->crsCode === $fixed_link->destination->crsCode)
                && $arrival_time !== null
                && (
                    !$existing 
                    || ($arrival_mode ? $arrival_time > $existing->getArrivalTime($fixed_link_departure_time) : $arrival_time < $existing->getArrivalTime($fixed_link_departure_time)) 
                    || $arrival_time == $existing->getArrivalTime($fixed_link_departure_time) && $fixed_link->priority > $existing->priority
                )
            ) {
                $fixed_links[$fixed_link->destination->crsCode] = $fixed_link;
            }
        }

        return ($this->viewResponseFactory)(
            new BoardView(
                new StreamFactory()
                , $self
                , $this->locationRepository->getAllStations()
                , $board
                , $date
                , $connecting_time
                , empty($query['connecting_toc']) ? null : $query['connecting_toc']
                , $station
                , $destination
                , $fixed_links
                , $fixed_link_departure_time
                , $permanent_only
                , empty($query['date'])
                , $arrival_mode
                , $service_repository->getGeneratedDate()
            )
        );
    }
}