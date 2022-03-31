<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models\Points;

use Miklcct\NationalRailJourneyPlanner\Models\Location;
use Miklcct\NationalRailJourneyPlanner\Models\ServiceProperty;
use Miklcct\NationalRailJourneyPlanner\Models\Time;

class CallingPoint extends IntermediatePoint implements HasDeparture, HasArrival {
    use ArrivalTrait;
    use DepartureTrait;

    public function __construct(
        Location $location
        , string $locationSuffix
        , string $platform
        , string $path
        , string $line
        , Time $workingArrival
        , ?Time $publicArrival
        , Time $workingDeparture
        , ?Time $publicDeparture
        , int $allowanceHalfMinutes
        , array $activities
        , ? ServiceProperty $servicePropertyChange
    ) {
        $this->publicDeparture = $publicDeparture;
        $this->workingDeparture = $workingDeparture;
        $this->publicArrival = $publicArrival;
        $this->workingArrival = $workingArrival;
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