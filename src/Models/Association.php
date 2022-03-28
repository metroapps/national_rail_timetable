<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use Miklcct\NationalRailJourneyPlanner\Enums\AssociationCategory;
use Miklcct\NationalRailJourneyPlanner\Enums\AssociationDay;
use Miklcct\NationalRailJourneyPlanner\Enums\AssociationType;
use Miklcct\NationalRailJourneyPlanner\Enums\ShortTermPlanning;

class Association extends AssociationEntry {
    public function __construct(
        string $primaryUid
        , string $secondaryUid
        , string $primarySuffix
        , string $secondarySuffix
        , Period $period
        , Location $location
        , public readonly AssociationCategory $category
        , public readonly AssociationDay $day
        , public readonly AssociationType $type
        , ShortTermPlanning $shortTermPlanning
    ) {
        parent::__construct(
            $primaryUid
            , $secondaryUid
            , $primarySuffix
            , $secondarySuffix
            , $period
            , $location
            , $shortTermPlanning
        );
    }
}