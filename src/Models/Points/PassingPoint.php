<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models\Points;

use Miklcct\NationalRailJourneyPlanner\Models\BsonSerializeTrait;
use Miklcct\NationalRailJourneyPlanner\Models\Location;
use Miklcct\NationalRailJourneyPlanner\Models\ServiceProperty;
use Miklcct\NationalRailJourneyPlanner\Models\Time;

class PassingPoint extends IntermediatePoint {
    use BsonSerializeTrait;

    public function __construct(
        Location $location
        , string $locationSuffix
        , string $platform
        , string $path
        , string $line
        , public readonly Time $pass
        , int $allowanceHalfMinutes
        , array $activities
        , ?ServiceProperty $servicePropertyChange
    ) {
        parent::__construct(
            $location
            , $locationSuffix
            , $platform
            , $path
            , $line
            , $allowanceHalfMinutes
            , $activities
            , $servicePropertyChange
        );
    }
}