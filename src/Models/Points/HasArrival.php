<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models\Points;

use Miklcct\NationalRailJourneyPlanner\Models\Time;

interface HasArrival {
    public function getWorkingArrival() : Time;
    public function getPublicArrival() : ?Time;
    public function getPublicOrWorkingArrival() : Time;
}