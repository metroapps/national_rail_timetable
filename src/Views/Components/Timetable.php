<?php
declare(strict_types = 1);

namespace Metroapps\NationalRailTimetable\Views\Components;

use Metroapps\NationalRailTimetable\Controllers\BoardQuery;
use Miklcct\RailOpenTimetableData\Models\Date;
use Miklcct\RailOpenTimetableData\Models\DepartureBoard;
use Miklcct\RailOpenTimetableData\Models\LocationWithCrs;
use Metroapps\NationalRailTimetable\Views\ViewMode;
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
}