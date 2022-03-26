<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateTimeImmutable;

class DatedAssociation {
    public function __construct(
        public readonly AssociationEntry $associationEntry
        , public readonly DateTimeImmutable $date
    ) {}
}