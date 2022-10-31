<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Views;

use Miklcct\NationalRailTimetable\Controllers\BoardQuery;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\DepartureBoard;
use Psr\Http\Message\StreamFactoryInterface;

class BoardView extends ScheduleView {
    public const URL = '/board.php';

    public function __construct(
        StreamFactoryInterface $streamFactory
        , array $stations
        , protected readonly DepartureBoard $board
        , Date $date
        , BoardQuery $query
        , ?array $fixedLinks
        , ?Date $generated
        , string $siteName
    ) {
        parent::__construct(
            $streamFactory
            , $stations
            , $date
            , $query
            , $fixedLinks
            , $generated
            , $siteName
        );
    }

    protected function getIncludePath() : string {
        return __DIR__ . '/../../resource/templates/board.phtml';
    }

    public function getViewMode() : ViewMode {
        return ViewMode::BOARD;
    }
}