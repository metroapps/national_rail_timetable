<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Enums;

enum ShortTermPlanning : string {
    case PERMANENT = 'P';
    case NEW = 'N';
    case OVERLAY = 'O';
    case CANCEL = 'C';
}