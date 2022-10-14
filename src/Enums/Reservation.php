<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Enums;

enum Reservation : string {
    case NONE = '';
    case BICYCLE = 'E';
    case AVAILABLE = 'S';
    case RECOMMENDED = 'R';
    case COMPULSORY = 'A';
}