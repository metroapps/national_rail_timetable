<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

class Station {
    public function __construct(
        public readonly string $crsCode
        , public readonly string $name
        , public readonly int $interchange
        , public readonly int $easting
        , public readonly int $northing
        , public readonly int $minimumConnectionTime
        , array $tocConnectionTimes
    ) {
        $this->tocConnectionTimes = $tocConnectionTimes;
    }

    /** @var TocInterchange[] */
    public readonly array $tocConnectionTimes;
}