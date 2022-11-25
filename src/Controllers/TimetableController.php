<?php
declare(strict_types = 1);

namespace Metroapps\NationalRailTimetable\Controllers;

use Miklcct\RailOpenTimetableData\Enums\TimeType;
use Miklcct\RailOpenTimetableData\Models\Date;
use Miklcct\RailOpenTimetableData\Models\LocationWithCrs;
use Miklcct\RailOpenTimetableData\Models\Time;
use Metroapps\NationalRailTimetable\Views\Components\Timetable;
use Metroapps\NationalRailTimetable\Views\ViewMode;
use function assert;

class TimetableController extends ScheduleController {
    public const URL = '/timetable.php';

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