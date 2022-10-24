<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Views;

use Miklcct\NationalRailTimetable\Controllers\BoardQuery;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\DepartureBoard;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Psr\Http\Message\StreamFactoryInterface;

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

   protected function getIncludePath() {
        return __DIR__ . '/../../resource/templates/timetable.phtml';
    }

    public function getViewMode() : ViewMode {
        return ViewMode::TIMETABLE;
    }
}