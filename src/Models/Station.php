<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

class Station extends Location {
    public function __construct(
        string $tiploc
        , string $crsCode
        , string $name
        , public readonly string $minorCrsCode
        , public readonly int $interchange
        , public readonly int $easting
        , public readonly int $northing
        , public readonly int $minimumConnectionTime
        , array $tocConnectionTimes
    ) {
        parent::__construct($tiploc, $crsCode, $name);
        $this->tocConnectionTimes = $tocConnectionTimes;
    }

    /** @var TocInterchange[] */
    public readonly array $tocConnectionTimes;
}