<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use Miklcct\NationalRailJourneyPlanner\Enums\ShortTermPlanning;
use MongoDB\BSON\Persistable;

abstract class AssociationEntry implements Persistable {
    use BsonSerializeTrait;

    public function __construct(
        public readonly string $primaryUid
        , public readonly string $secondaryUid
        , public readonly string $primarySuffix
        , public readonly string $secondarySuffix
        , public readonly Period $period
        , public readonly Location $location
        , public readonly ShortTermPlanning $shortTermPlanning
    ) {}
}