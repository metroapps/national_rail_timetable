<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

class OriginPoint extends TimingPoint {
    public function __construct(
        string $location
        , string $platform
        , public readonly string $line
        , public readonly Time $workingDeparture
        , public readonly ?Time $publicDeparture
        , public readonly int $allowanceHalfMinutes
        , array $activities
    ) {
        parent::__construct($location, $platform, $activities);
    }

    public function getPublicDeparture() : ?Time {
        return $this->publicDeparture;
    }
}