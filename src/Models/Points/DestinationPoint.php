<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Models\Points;

use Miklcct\NationalRailTimetable\Models\BsonSerializeTrait;
use Miklcct\NationalRailTimetable\Models\Location;
use Miklcct\NationalRailTimetable\Models\Time;

class DestinationPoint extends TimingPoint implements HasArrival {
    use BsonSerializeTrait;
    use ArrivalTrait;

    public function __construct(
        Location $location
        , string $locationSuffix
        , string $platform
        , public readonly string $path
        , Time $workingArrival
        , ?Time $publicArrival
        , array $activity
    ) {
        $this->publicArrival = $publicArrival;
        $this->workingArrival = $workingArrival;
        parent::__construct($location, $locationSuffix, $platform, $activity);
    }
}