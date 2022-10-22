<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Controllers;

use Http\Factory\Guzzle\StreamFactory;
use Miklcct\NationalRailTimetable\Enums\TimeType;
use Miklcct\NationalRailTimetable\Exceptions\StationNotFound;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Miklcct\NationalRailTimetable\Models\Time;
use Miklcct\NationalRailTimetable\Repositories\FixedLinkRepositoryInterface;
use Miklcct\NationalRailTimetable\Repositories\LocationRepositoryInterface;
use Miklcct\NationalRailTimetable\Repositories\ServiceRepositoryFactoryInterface;
use Miklcct\NationalRailTimetable\Views\BoardView;
use Miklcct\NationalRailTimetable\Views\ScheduleFormView;
use Miklcct\NationalRailTimetable\Views\ViewMode;
use Miklcct\ThinPhpApp\Controller\Application;
use Miklcct\ThinPhpApp\Response\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teapot\StatusCode\WebDAV;

class BoardController extends Application {
    use ScheduleTrait;

    public function __construct(
        private readonly ViewResponseFactoryInterface $viewResponseFactory
        , private readonly StreamFactoryInterface $streamFactory
        , private readonly LocationRepositoryInterface $locationRepository
        , private readonly ServiceRepositoryFactoryInterface $serviceRepositoryFactory
        , private readonly FixedLinkRepositoryInterface $fixedLinkRepository
    ) {}
    
    private function runWithoutCache(ServerRequestInterface $request, BoardQuery $query) : ResponseInterface {
        $station = $query->station;
        $destinations = $query->filter;
        if ($station === null) {
            return ($this->viewResponseFactory)(
                new ScheduleFormView(
                    new StreamFactory()
                    , $this->locationRepository->getAllStations()
                    , ViewMode::BOARD
                )
            );
        }

        $arrival_mode = $query->arrivalMode;
        $date = $query->date ?? Date::today();
        $from = $date->toDateTimeImmutable(new Time(0, 0));
        $to = $date->toDateTimeImmutable(new Time(28, 30));
        $permanent_only = $query->permanentOnly;
        $service_repository = ($this->serviceRepositoryFactory)($permanent_only);
        $board = $service_repository->getDepartureBoard(
            $station->getCrsCode()
            , $from
            , $to
            , $arrival_mode ? TimeType::PUBLIC_ARRIVAL : TimeType::PUBLIC_DEPARTURE
        );
        if ($destinations !== []) {
            $board = $board->filterByDestination(
                array_map(
                    static fn(LocationWithCrs $destination) => $destination->getCrsCode()
                    , $destinations
                )
            );
        }

        return ($this->viewResponseFactory)(
            new BoardView(
                new StreamFactory()
                , $this->locationRepository->getAllStations()
                , $board
                , $date
                , $query
                , $this->getFixedLinks($query)
                , $service_repository->getGeneratedDate()
            )
        );
    }

    private function createStationNotFoundResponse(StationNotFound $e) : ResponseInterface {
        return ($this->viewResponseFactory)(
            new ScheduleFormView(
                $this->streamFactory
                , $this->locationRepository->getAllStations()
                , ViewMode::BOARD
                , $e->getMessage()
            )
        )->withStatus(WebDAV::UNPROCESSABLE_ENTITY);
    }
}