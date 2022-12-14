<?php
declare(strict_types = 1);

namespace Metroapps\NationalRailTimetable\Controllers;

use Miklcct\RailOpenTimetableData\Enums\TimeType;
use Miklcct\RailOpenTimetableData\Models\Date;
use Miklcct\RailOpenTimetableData\Models\LocationWithCrs;
use Miklcct\RailOpenTimetableData\Models\Time;
use Metroapps\NationalRailTimetable\Views\Components\Board;
use Metroapps\NationalRailTimetable\Views\ViewMode;

class BoardController extends ScheduleController {
    public const URL = '/board';

    protected function getInnerView() : Board {
        $query = $this->getQuery();
        $station = $query->station;
        assert($station instanceof LocationWithCrs);
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
        $board = $board->filterByDestination(
            array_map(
                static fn(LocationWithCrs $destination) => $destination->getCrsCode()
                , $query->filter
            )
            , array_map(
                static fn(LocationWithCrs $destination) => $destination->getCrsCode()
                , $query->inverseFilter
            )
        );

        return new Board(
            $this->streamFactory
            , $board
            , $date
            , $query
        );
    }

    protected function getViewMode() : ViewMode {
        return ViewMode::BOARD;
    }
}