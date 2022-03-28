<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

class CallingPoint extends IntermediatePoint {
    public function __construct(
        Location $location
        , string $locationSuffix
        , string $platform
        , string $path
        , string $line
        , public readonly Time $workingArrival
        , public readonly ?Time $publicArrival
        , public readonly Time $workingDeparture
        , public readonly ?Time $publicDeparture
        , int $allowanceHalfMinutes
        , array $activities
        , ? ServiceProperty $servicePropertyChange
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

    public function getPublicArrival() : ?Time {
        return $this->publicArrival;
    }

    public function getPublicDeparture() : ?Time {
        return $this->publicDeparture;
    }
}