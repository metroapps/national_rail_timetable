<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateTimeImmutable;
use MongoDB\BSON\Persistable;

class CallCacheItem implements Persistable {
    use BsonSerializeTrait;

    public function __construct(
        public readonly DateTimeImmutable $callTime
        , public readonly ServiceCall $serviceCall
        , public readonly ServiceProperty $serviceProperty
    ) {

    }
}