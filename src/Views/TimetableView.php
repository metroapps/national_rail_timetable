<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Views;

use Miklcct\NationalRailTimetable\Controllers\BoardQuery;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\DepartureBoard;
use Miklcct\NationalRailTimetable\Models\Location;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Psr\Http\Message\StreamFactoryInterface;
use function array_map;
use function implode;
use function sprintf;

class TimetableView extends ScheduleView {
    public const URL = '/timetable.php';

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
        , Date $date
        , protected readonly array $boards
        , BoardQuery $query
        , array $stations
        , ?array $fixedLinks
        , ?Date $generated
    ) {
        parent::__construct($streamFactory, $stations, $date, $query, $fixedLinks, $generated);
    }

    protected function getTitle() : string {
        if ($this->query->station === null) {
            return 'Timetable';
        }
        return sprintf(
            '%s at %s %s %s'
            , $this->query->arrivalMode ? 'Arrivals' : 'Departures'
            , $this->query->station->name
            , $this->query->filter !== []
            ? ($this->query->arrivalMode ? ' from ' : ' to ') . implode(
                ', '
                , array_map(static fn(Location $location) => $location->name, $this->query->filter)
            )
            : ''
            , $this->query->date === null ? 'today' : 'on ' . $this->date
        );
    }

    protected function getIncludePath() {
        return __DIR__ . '/../../resource/templates/timetable.phtml';
    }

    public function getViewMode() : ViewMode {
        return ViewMode::TIMETABLE;
    }
}