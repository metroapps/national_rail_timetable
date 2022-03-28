<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models\Points;

use Miklcct\NationalRailJourneyPlanner\Enums\Activity;
use Miklcct\NationalRailJourneyPlanner\Models\Location;
use Miklcct\NationalRailJourneyPlanner\Models\Time;

class TimingPoint {
    public function __construct(
        public readonly Location $location
        , public readonly string $locationSuffix
        , public readonly string $platform
        , array $activity
    ) {
        $this->activity = $activity;
    }

    public function getPublicDeparture() : ?Time {
        return null;
    }

    public function getPublicArrival() : ?Time {
        return null;
    }

    public function isPublicCall() : bool {
        return
            ($this->getPublicDeparture() !== null || $this->getPublicArrival() !== null)
            && $this->location->crsCode !== null;
    }

    /** @var Activity[] */
    public readonly array $activity;
}