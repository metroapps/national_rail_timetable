<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models\Points;

use Miklcct\NationalRailJourneyPlanner\Models\BsonSerializeTrait;
use Miklcct\NationalRailJourneyPlanner\Models\Location;
use Miklcct\NationalRailJourneyPlanner\Models\ServiceProperty;

abstract class IntermediatePoint extends TimingPoint {
    use BsonSerializeTrait;

    public function __construct(
        Location $location
        , string $locationSuffix
        , string $platform
        , public readonly string $path
        , public readonly string $line
        , public readonly int $allowanceHalfMinutes
        , array $activity
        , public readonly ?ServiceProperty $servicePropertyChange
    ) {
        parent::__construct($location, $locationSuffix, $platform, $activity);
    }
}