<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models\Points;

use Miklcct\NationalRailJourneyPlanner\Models\BsonSerializeTrait;
use Miklcct\NationalRailJourneyPlanner\Models\Location;
use Miklcct\NationalRailJourneyPlanner\Models\ServiceProperty;
use Miklcct\NationalRailJourneyPlanner\Models\Time;

class OriginPoint extends TimingPoint implements HasDeparture {
    use DepartureTrait;
    use BsonSerializeTrait;

    public function __construct(
        Location $location
        , string $locationSuffix
        , string $platform
        , public readonly string $line
        , Time $workingDeparture
        , ?Time $publicDeparture
        , public readonly int $allowanceHalfMinutes
        , array $activity
        , public readonly ServiceProperty $serviceProperty
    ) {
        $this->publicDeparture = $publicDeparture;
        $this->workingDeparture = $workingDeparture;
        parent::__construct($location, $locationSuffix, $platform, $activity);
    }
}