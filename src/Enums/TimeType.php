<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Enums;

enum TimeType : string {
    case WORKING = 'working';
    case PUBLIC = 'public';
}