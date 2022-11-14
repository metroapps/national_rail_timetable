<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Controllers;

use Miklcct\NationalRailTimetable\Config\Config;
use Miklcct\NationalRailTimetable\Enums\TimeType;
use Miklcct\NationalRailTimetable\Exceptions\StationNotFound;
use Miklcct\NationalRailTimetable\Middlewares\CacheMiddleware;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Miklcct\NationalRailTimetable\Models\Time;
use Miklcct\NationalRailTimetable\Repositories\FixedLinkRepositoryInterface;
use Miklcct\NationalRailTimetable\Repositories\LocationRepositoryInterface;
use Miklcct\NationalRailTimetable\Repositories\ServiceRepositoryFactoryInterface;
use Miklcct\NationalRailTimetable\Views\ScheduleFormView;
use Miklcct\NationalRailTimetable\Views\Components\Timetable;
use Miklcct\NationalRailTimetable\Views\ScheduleView;
use Miklcct\NationalRailTimetable\Views\ViewMode;
use Miklcct\ThinPhpApp\Controller\Application;
use Miklcct\ThinPhpApp\Response\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teapot\StatusCode\WebDAV;
use function assert;

class TimetableController extends ScheduleController {
    // this number must be greater than the maximum number of calls for a train
    public const URL = '/timetable.php';
    private const MULTIPLIER = 1000;

    protected function getInnerView() : Timetable {
        $query = $this->getQuery();
        $station = $query->station;
        assert($station instanceof LocationWithCrs);
        $date = $query->date ?? Date::today();
        $service_repository = ($this->serviceRepositoryFactory)($query->permanentOnly);
        $board = $service_repository->getDepartureBoard(
            $station->getCrsCode()
            , $date->toDateTimeImmutable()
            , $date->toDateTimeImmutable(new Time(28, 30))
            , $query->arrivalMode ? TimeType::PUBLIC_ARRIVAL : TimeType::PUBLIC_DEPARTURE
        );
        $board = $board->filterByDestination(
            array_map(static fn(LocationWithCrs $location) => $location->getCrsCode(), $query->filter)
            , array_map(static fn(LocationWithCrs $location) => $location->getCrsCode(), $query->inverseFilter)
        );

        return new Timetable(
            $this->streamFactory
            , $date
            , $board->groupServices()
            , $query
        );
    }

    protected function getViewMode() : ViewMode {
        return ViewMode::TIMETABLE;
    }
}