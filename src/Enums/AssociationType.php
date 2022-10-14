<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Enums;

enum AssociationType : string {
    case PASSENGER = 'P';
    case OPERATING = 'O';
}