<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models\Points;

use Miklcct\NationalRailJourneyPlanner\Models\Time;

interface HasDeparture {
    public function getWorkingDeparture() : Time;
    public function getPublicDeparture() : ?Time;
    public function getPublicOrWorkingDeparture() : Time;
}