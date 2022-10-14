<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailJourneyPlanner\Controllers;

use DateTimeZone;
use DateTimeImmutable;
use DateInterval;
use Miklcct\NationalRailJourneyPlanner\Models\Station;
use Miklcct\NationalRailJourneyPlanner\Enums\TimeType;
use Miklcct\NationalRailJourneyPlanner\Views\BoardFormView;
use Miklcct\ThinPhpApp\Controller\Application;
use Miklcct\ThinPhpApp\Response\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Http\Factory\Guzzle\StreamFactory;
use InvalidArgumentException;
use Miklcct\NationalRailJourneyPlanner\Repositories\FixedLinkRepositoryInterface;
use Miklcct\NationalRailJourneyPlanner\Repositories\LocationRepositoryInterface;
use Miklcct\NationalRailJourneyPlanner\Repositories\ServiceRepositoryInterface;
use Miklcct\NationalRailJourneyPlanner\Views\BoardView;

class BoardController extends Application {
    public function __construct(
        private readonly ViewResponseFactoryInterface $viewResponseFactory
        , private readonly LocationRepositoryInterface $locationRepository
        , private readonly ServiceRepositoryInterface $serviceRepository
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
                    , $this->locationRepository->getAllStationNames()
                )
            );
        }

        $station = $this->locationRepository->getLocationByCrs($query['station'])
            ?? $this->locationRepository->getLocationByName($query['station']);
        if ($station?->crsCode === null) {
            throw new InvalidArgumentException('Station cannot be found.');
        }

        $destination = null;
        if (!empty($query['filter'])) {
            $destination = $this->locationRepository->getLocationByCrs($query['filter'])
                ?? $this->locationRepository->getLocationByName($query['filter']);
            if ($destination?->crsCode === null) {
                throw new InvalidArgumentException('Destination cannot be found.');
            }
        }

        $timezone = new DateTimeZone('Europe/London');
        $from = new DateTimeImmutable(empty($query['from']) ? 'now' : $query['from'], $timezone);
        $from = $from->setTime((int)$from->format('H'), (int)$from->format('i'), 0);
        $to = $from->add(new DateInterval('P1DT4H30M'));
        $connecting_time = !empty($_GET['connecting_time']) ? new DateTimeImmutable($_GET['connecting_time'], $timezone) : null;
        $board = $this->serviceRepository->getDepartureBoard($station->crsCode, $from, $to, TimeType::PUBLIC_DEPARTURE);
        if ($destination !== null) {
            $board = $board->filterByDestination($destination->crsCode);
        }

        /** @var FixedLink[] */
        $fixed_links = [];
        $fixed_link_departure_time = isset($connecting_time) && $station instanceof Station ? $connecting_time->add(new DateInterval(sprintf('PT%dM', $station->minimumConnectionTime))) : $from;
        foreach ($this->fixedLinkRepository->get($station->crsCode, null) as $fixed_link) {
            $arrival_time = $fixed_link->getArrivalTime($fixed_link_departure_time);
            $existing = $fixed_links[$fixed_link->destination->crsCode] ?? null;
            if (
                ($destination === null || $destination->crsCode === $fixed_link->destination->crsCode)
                && (
                    !$existing 
                    || $arrival_time < $existing->getArrivalTime($fixed_link_departure_time) 
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
                , $this->locationRepository->getAllStationNames()
                , $board
                , empty($query['from']) ? null : $from
                , $connecting_time
                , empty($query['connecting_toc']) ? null : $query['connecting_toc']
                , $station
                , $destination
                , $fixed_links
                , $fixed_link_departure_time
                , !empty($query['permanent_only'])
            )
        );
    }
}