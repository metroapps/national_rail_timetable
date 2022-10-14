<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Models\Points;

use Miklcct\NationalRailTimetable\Models\BsonSerializeTrait;
use Miklcct\NationalRailTimetable\Models\Location;
use Miklcct\NationalRailTimetable\Models\ServiceProperty;
use Miklcct\NationalRailTimetable\Models\Time;

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
        , array $activity
        , ?ServiceProperty $serviceProperty
    ) {
        parent::__construct(
            $location
            , $locationSuffix
            , $platform
            , $path
            , $line
            , $allowanceHalfMinutes
            , $activity
            , $serviceProperty
        );
    }

    /**
     * @return Time
     */
    public function getPass() : Time {
        return $this->pass;
    }
}