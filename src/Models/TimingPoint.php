<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use Miklcct\NationalRailJourneyPlanner\Enums\Activity;

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

    /** @var Activity[] */
    public readonly array $activity;
}