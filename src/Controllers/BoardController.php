<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Controllers;

use DateInterval;
use DateTimeZone;
use Http\Factory\Guzzle\StreamFactory;
use Miklcct\NationalRailTimetable\Enums\TimeType;
use Miklcct\NationalRailTimetable\Exceptions\StationNotFound;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\FixedLink;
use Miklcct\NationalRailTimetable\Models\Station;
use Miklcct\NationalRailTimetable\Models\Time;
use Miklcct\NationalRailTimetable\Repositories\FixedLinkRepositoryInterface;
use Miklcct\NationalRailTimetable\Repositories\LocationRepositoryInterface;
use Miklcct\NationalRailTimetable\Repositories\ServiceRepositoryFactoryInterface;
use Miklcct\NationalRailTimetable\Views\BoardFormView;
use Miklcct\NationalRailTimetable\Views\BoardView;
use Miklcct\ThinPhpApp\Controller\Application;
use Miklcct\ThinPhpApp\Response\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Safe\DateTimeImmutable as SafeDateTimeImmutable;
use Teapot\StatusCode\WebDAV;

class BoardController extends Application {
    use QueryTrait;

    public function __construct(
        private readonly ViewResponseFactoryInterface $viewResponseFactory
        , private readonly StreamFactoryInterface $streamFactory
        , private readonly LocationRepositoryInterface $locationRepository
        , private readonly ServiceRepositoryFactoryInterface $serviceRepositoryFactory
        , private readonly FixedLinkRepositoryInterface $fixedLinkRepository
    ) {}
    
    public function run(ServerRequestInterface $request) : ResponseInterface {
        try {
            $query = BoardQuery::fromArray($request->getQueryParams(), $this->locationRepository);
            $station = $query->station;
            $destination = $query->filter;
        } catch (StationNotFound $e) {
            return ($this->viewResponseFactory)(
                new BoardFormView(
                    $this->streamFactory
                    , $this->locationRepository->getAllStations()
                    , $e->getMessage()
                )
            )->withStatus(WebDAV::UNPROCESSABLE_ENTITY);
        }

        if ($station === null) {
            return ($this->viewResponseFactory)(
                new BoardFormView(
                    new StreamFactory()
                    , $this->locationRepository->getAllStations()
                )
            )->withAddedHeader('Cache-Control', ['public', 'max-age=604800']);
        }

        $arrival_mode = $query->arrivalMode;
        $date = $query->date ?? Date::today();
        $from = $date->toDateTimeImmutable(new Time(0, 0));
        $to = $date->toDateTimeImmutable(new Time(28, 30));
        $connecting_time = $query->connectingTime;
        $permanent_only = $query->permanentOnly;
        $service_repository = ($this->serviceRepositoryFactory)($permanent_only);
        $board = $service_repository->getDepartureBoard(
            $station->getCrsCode()
            , $from
            , $to
            , $arrival_mode ? TimeType::PUBLIC_ARRIVAL : TimeType::PUBLIC_DEPARTURE
        );
        if ($destination !== null) {
            $board = $board->filterByDestination($destination->getCrsCode());
        }

        /** @var FixedLink[] $fixed_links */
        $fixed_links = [];
        $fixed_link_departure_time 
            = isset($connecting_time) && $station instanceof Station 
                ? $arrival_mode 
                    ? $connecting_time->sub(new DateInterval(sprintf('PT%dM', $station->minimumConnectionTime))) 
                    : $connecting_time->add(new DateInterval(sprintf('PT%dM', $station->minimumConnectionTime))) 
                : null;
        foreach ($this->fixedLinkRepository->get($station->crsCode, null) as $fixed_link) {
            if ($fixed_link_departure_time !== null) {
                $arrival_time = $fixed_link->getArrivalTime($fixed_link_departure_time, $arrival_mode);
                $existing = $fixed_links[$fixed_link->destination->crsCode] ?? null;
                if (
                    ($destination === null || $destination->getCrsCode() === $fixed_link->destination->crsCode)
                    && $arrival_time !== null
                    && (
                        !$existing 
                        || ($arrival_mode ? $arrival_time > $existing->getArrivalTime($fixed_link_departure_time) : $arrival_time < $existing->getArrivalTime($fixed_link_departure_time)) 
                        || $arrival_time == $existing->getArrivalTime($fixed_link_departure_time) && $fixed_link->priority > $existing->priority
                    )
                ) {
                    $fixed_links[$fixed_link->destination->crsCode] = $fixed_link;
                }
            } elseif ($fixed_link->isActiveOnDate($date)) {
                $fixed_links[] = $fixed_link;
            }
        }

        usort(
            $fixed_links
            , static fn(FixedLink $a, FixedLink $b) =>
                $a->origin->crsCode === $b->origin->crsCode 
                    ? $a->destination->crsCode === $b->destination->crsCode 
                        ? $a->startTime->toHalfMinutes() <=> $b->startTime->toHalfMinutes() 
                        : $a->destination->crsCode <=> $b->destination->crsCode 
                    : $a->origin->crsCode <=> $b->origin->crsCode
        );

        $response = ($this->viewResponseFactory)(
            new BoardView(
                new StreamFactory()
                , $this->locationRepository->getAllStations()
                , $board
                , $date
                , $query
                , $fixed_links
                , $fixed_link_departure_time
                , $service_repository->getGeneratedDate()
            )
        )->withAddedHeader('Cache-Control', ['public']);
        return $query->date === null
            ? $response->withAddedHeader(
                'Expires',
                str_replace(
                    '+0000',
                    'GMT',
                    (new SafeDateTimeImmutable('tomorrow'))->setTimezone(new DateTimeZone('UTC'))->format('r')
                )
            )
            : $response->withAddedHeader('Cache-Control', 'max-age=21600');
    }

}