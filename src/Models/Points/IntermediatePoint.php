<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Models\Points;

use Miklcct\NationalRailTimetable\Models\BsonSerializeTrait;
use Miklcct\NationalRailTimetable\Models\Location;
use Miklcct\NationalRailTimetable\Models\ServiceProperty;

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
        , public readonly ?ServiceProperty $serviceProperty
    ) {
        parent::__construct($location, $locationSuffix, $platform, $activity);
    }
}