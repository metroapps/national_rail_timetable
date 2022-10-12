<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateTimeImmutable;
use Miklcct\NationalRailJourneyPlanner\Enums\TimeType;
use Miklcct\NationalRailJourneyPlanner\Models\Points\TimingPoint;
use MongoDB\BSON\Persistable;
use DateInterval;

class ServiceCall implements Persistable {
    use BsonSerializeTrait;

    public function __construct(
        public readonly DateTimeImmutable $timestamp
        , public readonly TimeType $timeType
        , public readonly string $uid
        , public readonly Date $date
        , public readonly TimingPoint $call
        , public readonly string $toc
        , public readonly ServiceProperty $serviceProperty
    ) {}

    public function isValidConnection(DateTimeImmutable $time, ?string $other_toc = null) : bool {
        $station = $this->call->location;
        if (!$station instanceof Station) {
            return false;
        }
        return match ($this->timeType) {
            TimeType::PUBLIC_DEPARTURE => $this->timestamp >= $time->add(new DateInterval(sprintf('PT%dM', $station->getConnectionTime($other_toc, $this->toc)))),
            TimeType::PUBLIC_ARRIVAL => $time >= $this->timestamp->add(new DateInterval(sprintf('PT%dM', $station->getConnectionTime($this->toc, $other_toc)))),
            default => false,
        };
    }
}