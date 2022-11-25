<?php
declare(strict_types=1);

namespace Metroapps\NationalRailTimetable\Views;

use Metroapps\NationalRailTimetable\Controllers\BoardController;
use Metroapps\NationalRailTimetable\Controllers\TimetableController;

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
