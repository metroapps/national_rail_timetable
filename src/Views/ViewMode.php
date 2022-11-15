<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Views;

use Miklcct\NationalRailTimetable\Controllers\BoardController;
use Miklcct\NationalRailTimetable\Controllers\TimetableController;

enum ViewMode {
    case BOARD;
    case TIMETABLE;

    public function getUrl() : string {
        return match ($this) {
            self::BOARD => BoardController::URL,
            self::TIMETABLE => TimetableController::URL,
        };
    }
}
