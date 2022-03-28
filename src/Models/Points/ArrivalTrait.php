<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models\Points;

use Miklcct\NationalRailJourneyPlanner\Models\Time;

trait ArrivalTrait {
    public readonly Time $workingArrival;
    public readonly ?Time $publicArrival;

    public function getWorkingArrival() : Time {
        return $this->workingArrival;
    }

    public function getPublicArrival() : ?Time {
        return $this->publicArrival;
    }

    public function getPublicOrWorkingArrival() : Time {
        return $this->getPublicArrival() ?? $this->getWorkingArrival();
    }
}