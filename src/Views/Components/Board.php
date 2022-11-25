<?php
declare(strict_types = 1);

namespace Metroapps\NationalRailTimetable\Views\Components;

use Metroapps\NationalRailTimetable\Controllers\BoardController;
use Metroapps\NationalRailTimetable\Controllers\BoardQuery;
use Miklcct\RailOpenTimetableData\Models\Date;
use Miklcct\RailOpenTimetableData\Models\DepartureBoard;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;

class Board extends PhpTemplate {

    public function __construct(
        StreamFactoryInterface $streamFactory
        , protected readonly DepartureBoard $board
        , protected readonly Date $date
        , protected readonly BoardQuery $query
    ) {
        parent::__construct($streamFactory);
    }

    protected function getPathToTemplate() : string {
        return __DIR__ . '/../../../resource/templates/board.phtml';
    }

    protected function getDayOffsetLink(int $days) : string {
        return (new BoardQuery(
            $this->query->arrivalMode
            , $this->query->station
            , $this->query->filter
            , $this->query->inverseFilter
            , $this->date->addDays($days)
            , $this->query->connectingTime
            , $this->query->connectingToc
            , $this->query->permanentOnly
        ))->getUrl(BoardController::URL);
    }

}