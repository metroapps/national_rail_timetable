<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Enums;

enum CallType {
    case DEPARTURE;
    case ARRIVAL;
    case PASS;
}