<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Enums;

enum Mode : string {
    case TRAIN = '';
    case BUS = 'B';
    case SHIP = 'S';
}