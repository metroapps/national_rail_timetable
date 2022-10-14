<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Enums;

enum AssociationDay : string {
    case YESTERDAY = 'P';
    case TODAY = 'S';
    case TOMORROW = 'N';
}