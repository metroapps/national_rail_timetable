<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models\Points;

use Miklcct\NationalRailJourneyPlanner\Attributes\ElementType;
use Miklcct\NationalRailJourneyPlanner\Enums\Activity;
use Miklcct\NationalRailJourneyPlanner\Enums\TimeType;
use Miklcct\NationalRailJourneyPlanner\Models\BsonSerializeTrait;
use Miklcct\NationalRailJourneyPlanner\Models\Location;
use Miklcct\NationalRailJourneyPlanner\Models\Time;
use MongoDB\BSON\Persistable;

abstract class TimingPoint implements Persistable {
    use BsonSerializeTrait;

    public function __construct(
        public readonly Location $location
        , public readonly string $locationSuffix
        , public readonly string $platform
        , array $activities
    ) {
        $this->activities = $activities;
    }

    public function getTime(TimeType $time_type) : ?Time {
        return match ($time_type) {
            TimeType::WORKING_ARRIVAL => $this instanceof HasArrival ? $this->getWorkingArrival() : null,
            TimeType::PUBLIC_ARRIVAL => $this instanceof HasArrival ? $this->getPublicArrival() : null,
            TimeType::PASS => $this instanceof PassingPoint ? $this->pass : null,
            TimeType::PUBLIC_DEPARTURE => $this instanceof HasDeparture ? $this->getPublicDeparture() : null,
            TimeType::WORKING_DEPARTURE => $this instanceof HasDeparture ? $this->getWorkingDeparture() : null,
        };
    }

    public function isPublicCall() : bool {
        return
            (
                $this instanceof HasDeparture && $this->getPublicDeparture() !== null
                || $this instanceof HasArrival && $this->getPublicArrival() !== null
            );
            // && $this->location->crsCode !== null;
    }

    /** @var Activity[] */
    #[ElementType(Activity::class)]
    public readonly array $activities;
}