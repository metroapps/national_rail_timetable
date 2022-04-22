<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use MongoDB\BSON\Persistable;

class TocInterchange implements Persistable {
    public function __construct(
        public readonly string $arrivingToc
        , public readonly string $departingToc
        , public readonly int $connectionTime
    ) {}

    public function bsonSerialize() : array {
        return (array)$this;
    }

    public function bsonUnserialize(array $data) : self {
        return new self($data['arrivingToc'], $data['departingToc'], $data['connectionTime']);
    }
}