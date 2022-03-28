<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models\Points;

use Miklcct\NationalRailJourneyPlanner\Models\Location;
use Miklcct\NationalRailJourneyPlanner\Models\Time;

class OriginPoint extends TimingPoint implements HasDeparture {
    use DepartureTrait;

    public function __construct(
        Location $location
        , string $locationSuffix
        , string $platform
        , public readonly string $line
        , Time $workingDeparture
        , ?Time $publicDeparture
        , public readonly int $allowanceHalfMinutes
        , array $activities
    ) {
        $this->publicDeparture = $publicDeparture;
        $this->workingDeparture = $workingDeparture;
        parent::__construct($location, $locationSuffix, $platform, $activities);
    }
}