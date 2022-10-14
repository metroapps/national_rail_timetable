<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Models\Points;

use Miklcct\NationalRailTimetable\Enums\Activity;
use Miklcct\NationalRailTimetable\Models\Time;

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