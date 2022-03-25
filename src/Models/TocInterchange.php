<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

class TocInterchange {
    public function __construct(
        public readonly string $arrivingToc
        , public readonly string $departingToc
        , public readonly int $connectionTime
    ) {}
}