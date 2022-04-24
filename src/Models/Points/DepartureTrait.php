<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models\Points;

use Miklcct\NationalRailJourneyPlanner\Enums\Activity;
use Miklcct\NationalRailJourneyPlanner\Models\Time;

trait DepartureTrait {
    public readonly Time $workingDeparture;
    public readonly ?Time $publicDeparture;

    public function getWorkingDeparture() : Time {
        return $this->workingDeparture;
    }

    public function getPublicDeparture() : ?Time {
        return in_array(Activity::UNADVERTISED, $this->activities, true) ? null : $this->publicDeparture;
    }

    public function getPublicOrWorkingDeparture() : Time {
        return $this->getPublicDeparture() ?? $this->getWorkingDeparture();
    }

}