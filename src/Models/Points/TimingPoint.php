<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models\Points;

use Miklcct\NationalRailJourneyPlanner\Attributes\ElementType;
use Miklcct\NationalRailJourneyPlanner\Enums\Activity;
use Miklcct\NationalRailJourneyPlanner\Enums\CallType;
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

    public function getTime(CallType $call_type, TimeType $time_type) : ?Time {
        return match ($call_type) {
            CallType::DEPARTURE => $this instanceof HasDeparture ? match ($time_type) {
                TimeType::PUBLIC => $this->getPublicDeparture(),
                TimeType::WORKING => $this->getWorkingDeparture(),
            } : null,
            CallType::ARRIVAL => $this instanceof HasArrival ? match ($time_type) {
                TimeType::PUBLIC => $this->getPublicArrival(),
                TimeType::WORKING => $this->getWorkingArrival(),
            } : null,
            CallType::PASS => $this->pass ?? null,
        };
    }

    public function isPublicCall() : bool {
        return
            (
                $this instanceof HasDeparture && $this->getPublicDeparture() !== null
                || $this instanceof HasArrival && $this->getPublicArrival() !== null
            )
            && $this->location->crsCode !== null;
    }

    /** @var Activity[] */
    #[ElementType(Activity::class)]
    public readonly array $activities;
}