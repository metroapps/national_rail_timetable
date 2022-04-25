<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Enums;

enum CallType : string {
    case DEPARTURE = 'departure';
    case ARRIVAL = 'arrival';
    case PASS = 'pass';
}