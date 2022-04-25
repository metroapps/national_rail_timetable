<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateTimeImmutable;
use Miklcct\NationalRailJourneyPlanner\Enums\TimeType;
use Miklcct\NationalRailJourneyPlanner\Models\Points\TimingPoint;
use MongoDB\BSON\Persistable;

class ServiceCall implements Persistable {
    use BsonSerializeTrait;

    public function __construct(
        public readonly DateTimeImmutable $timestamp
        , public readonly TimeType $timeType
        , public readonly DatedService $datedService
        , public readonly TimingPoint $call
        , public readonly ServiceProperty $serviceProperty
    ) {}
}