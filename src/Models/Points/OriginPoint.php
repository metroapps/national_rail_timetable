<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Models\Points;

use Miklcct\NationalRailTimetable\Models\BsonSerializeTrait;
use Miklcct\NationalRailTimetable\Models\Location;
use Miklcct\NationalRailTimetable\Models\ServiceProperty;
use Miklcct\NationalRailTimetable\Models\Time;

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