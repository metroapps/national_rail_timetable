<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Models;

use Miklcct\NationalRailTimetable\Enums\AssociationCategory;
use Miklcct\NationalRailTimetable\Enums\AssociationDay;
use Miklcct\NationalRailTimetable\Enums\AssociationType;
use Miklcct\NationalRailTimetable\Enums\ShortTermPlanning;

class Association extends AssociationEntry {
    use BsonSerializeTrait;

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