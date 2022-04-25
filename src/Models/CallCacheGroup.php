<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateTimeImmutable;
use Miklcct\NationalRailJourneyPlanner\Attributes\ElementType;
use Miklcct\NationalRailJourneyPlanner\Enums\CallType;
use Miklcct\NationalRailJourneyPlanner\Enums\TimeType;
use MongoDB\BSON\Persistable;

class CallCacheGroup implements Persistable {
    use BsonSerializeTrait;

    public function __construct(
        public readonly string $crs
        , public readonly DateTimeImmutable $from
        , public readonly DateTimeImmutable $to
        , public readonly CallType $callType
        , public readonly TimeType $timeType
        , array $calls
    ) {
        $this->calls = $calls;
    }

    /** @var CallCacheItem[] */
    #[ElementType(CallCacheItem::class)]
    public readonly array $calls;
}