<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models\Points;

use Miklcct\NationalRailJourneyPlanner\Attributes\ElementType;
use Miklcct\NationalRailJourneyPlanner\Enums\Activity;
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

    public function getPublicDeparture() : ?Time {
        return null;
    }

    public function getPublicArrival() : ?Time {
        return null;
    }

    public function isPublicCall() : bool {
        return
            ($this->getPublicDeparture() !== null || $this->getPublicArrival() !== null)
            && $this->location->crsCode !== null;
    }

    /** @var Activity[] */
    #[ElementType(Activity::class)]
    public readonly array $activities;
}