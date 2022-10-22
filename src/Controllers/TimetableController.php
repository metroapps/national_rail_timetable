<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Controllers;

use Miklcct\NationalRailTimetable\Enums\TimeType;
use Miklcct\NationalRailTimetable\Exceptions\StationNotFound;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Miklcct\NationalRailTimetable\Models\Time;
use Miklcct\NationalRailTimetable\Repositories\FixedLinkRepositoryInterface;
use Miklcct\NationalRailTimetable\Repositories\LocationRepositoryInterface;
use Miklcct\NationalRailTimetable\Repositories\ServiceRepositoryFactoryInterface;
use Miklcct\NationalRailTimetable\Views\TimetableView;
use Miklcct\ThinPhpApp\Controller\Application;
use Miklcct\ThinPhpApp\Response\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teapot\StatusCode\WebDAV;

class TimetableController extends Application {
    use ScheduleTrait;

    // this number must be greater than the maximum number of calls for a train
    private const MULTIPLIER = 1000;

    public function __construct(
        private readonly ViewResponseFactoryInterface $viewResponseFactory
        , private readonly StreamFactoryInterface $streamFactory
        , private readonly ServiceRepositoryFactoryInterface $serviceRepositoryFactory
        , private readonly LocationRepositoryInterface $locationRepository
        , private readonly FixedLinkRepositoryInterface $fixedLinkRepository
    ) {}

    public function runWithoutCache(ServerRequestInterface $request, BoardQuery $query) : ResponseInterface {
        $station = $query->station;
        if ($station === null) {
            return ($this->viewResponseFactory)(
                new TimetableView(
                    $this->streamFactory
                    , null
                    , Date::today()
                    , []
                    , new $query
                    , $this->locationRepository->getAllStations()
                    , []
                    , null
                )
            );
        }
        $date = $query->date ?? Date::today();
        $service_repository = ($this->serviceRepositoryFactory)($query->permanentOnly);
        $board = $service_repository->getDepartureBoard(
            $station->getCrsCode()
            , $date->toDateTimeImmutable()
            , $date->toDateTimeImmutable(new Time(28, 30))
            , $query->arrivalMode ? TimeType::PUBLIC_ARRIVAL : TimeType::PUBLIC_DEPARTURE
        );
        $filter = $query->filter;
        if ($filter !== []) {
            $board = $board->filterByDestination(
                array_map(static fn(LocationWithCrs $location) => $location->getCrsCode(), $filter)
                , true
            );
        }

        return ($this->viewResponseFactory)(
            new TimetableView(
                $this->streamFactory
                , $station
                , $date
                , $board->groupServices()
                , $query
                , $this->locationRepository->getAllStations()
                , $this->getFixedLinks($query)
                , $service_repository->getGeneratedDate()
            )
        );
    }

    private function createStationNotFoundResponse(StationNotFound $e) : ResponseInterface {
        return ($this->viewResponseFactory)(
            new TimetableView(
                $this->streamFactory
                , null
                , Date::today()
                , []
                , new BoardQuery()
                , $this->locationRepository->getAllStations()
                , []
                , null
                , $e->getMessage()
            )
        )->withStatus(WebDAV::UNPROCESSABLE_ENTITY);
    }
}