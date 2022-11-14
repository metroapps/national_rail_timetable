<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Views\Components;

use Miklcct\NationalRailTimetable\Controllers\BoardController;
use Miklcct\NationalRailTimetable\Controllers\BoardQuery;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\DepartureBoard;
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