<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Enums;

enum TimeType : string {
    case WORKING_ARRIVAL = 'working_arrival';
    case PUBLIC_ARRIVAL = 'public_arrival';
    case PASS = 'pass';
    case PUBLIC_DEPARTURE = 'public_departure';
    case WORKING_DEPARTURE = 'working_departure';
}