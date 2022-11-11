<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Views;

enum ViewMode {
    case BOARD;
    case TIMETABLE;

    public function getUrl() : string {
        return match ($this) {
            self::BOARD => BoardView::URL,
            self::TIMETABLE => TimetableView::URL,
        };
    }
}
