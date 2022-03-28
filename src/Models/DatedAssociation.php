<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

class DatedAssociation {
    public function __construct(
        public readonly AssociationEntry $associationEntry
        , public readonly DatedService $primaryService
        , public readonly DatedService $secondaryService
    ) {}
}