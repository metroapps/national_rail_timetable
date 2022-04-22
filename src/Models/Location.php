<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use MongoDB\BSON\Persistable;

class Location implements Persistable {
    use BsonSerializeTrait;

    public function __construct(
        public readonly string $tiploc
        , public readonly ?string $crsCode
        , public readonly string $name
    ) {}

    public function isSuperior(?Location $existing) : bool {
        return $existing === null
            || $this instanceof Station
            && (
                !$existing instanceof Station
                || $this->minorCrsCode === $this->crsCode
                && $existing->minorCrsCode !== $existing->crsCode
                || $this->interchange !== 9 && $existing->interchange === 9
            );
    }
}