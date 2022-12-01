<?php
declare(strict_types = 1);

namespace Metroapps\NationalRailTimetable\Views\Components;

use Metroapps\NationalRailTimetable\Controllers\BoardQuery;
use Miklcct\RailOpenTimetableData\Models\Date;
use Miklcct\RailOpenTimetableData\Models\DepartureBoard;
use Miklcct\RailOpenTimetableData\Models\LocationWithCrs;
use Metroapps\NationalRailTimetable\Views\ViewMode;
use Miklcct\RailOpenTimetableData\Models\Timetable as TimetableModel;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;

class Timetable extends PhpTemplate {

    /**
     * @param StreamFactoryInterface $streamFactory
     * @param LocationWithCrs $station
     * @param Date $date
     * @param DepartureBoard[] $boards
     * @param BoardQuery $query
     * @param LocationWithCrs[] $stations
     * @param string|null $errorMessage
     */
    public function __construct(
        StreamFactoryInterface $streamFactory
        , protected readonly Date $date
        , protected readonly array $boards
        , protected readonly BoardQuery $query
    ) {
        parent::__construct(
            $streamFactory
        );
    }

   protected function getPathToTemplate() : string {
        return __DIR__ . '/../../../resource/templates/timetable.phtml';
    }

    public function getViewMode() : ViewMode {
        return ViewMode::TIMETABLE;
    }

    /**
     * @param TimetableModel $timetable
     * @return LocationWithCrs[]
     */
    protected function getShownRows(TimetableModel $timetable) : array {
        $filter_crs = array_map(
            static fn(LocationWithCrs $filter_station) => $filter_station->getCrsCode()
            , $this->query->filter
        );
        return array_filter(
            $timetable->stations
            , fn(LocationWithCrs $station, int $key) =>
                $this->query->filter === []
                || $key === 0
                || in_array($station->getCrsCode(), $filter_crs, true)
                || array_filter(
                    $timetable->stations
                    , fn(LocationWithCrs $other_station, int $other_key) =>
                        ($this->query->arrivalMode ? $other_key < $key : $other_key > $key)
                        && in_array($other_station->getCrsCode(), $filter_crs, true)
                        && array_filter(
                            $timetable->calls[$key]
                            , static fn(string $uid_date) => isset($timetable->calls[$other_key][$uid_date])
                            , ARRAY_FILTER_USE_KEY
                        ) !== []
                    , ARRAY_FILTER_USE_BOTH
                ) !== []
            , ARRAY_FILTER_USE_BOTH
        );
    }
}