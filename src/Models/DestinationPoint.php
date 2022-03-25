<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

class DestinationPoint extends TimingPoint {
    public function __construct(
        string $location
        , string $platform
        , public readonly string $path
        , public readonly Time $workingArrival
        , public readonly ?Time $publicArrival
        , array $activity
    ) {
        parent::__construct($location, $platform, $activity);
    }

    public function getPublicArrival() : ?Time {
        return $this->publicArrival;
    }
}