<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use Miklcct\NationalRailJourneyPlanner\Attributes\ElementType;

class Station extends Location {
    use BsonSerializeTrait;

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

    public function getConnectionTime(string $from_toc, string $to_toc) : int {
        foreach ($this->tocConnectionTimes as $interchange) {
            if ($interchange->arrivingToc === $from_toc && $interchange->departingToc === $to_toc) {
                return $interchange->connectionTime;
            }
        }
        return $this->minimumConnectionTime;
    }

    /** @var TocInterchange[] */
    #[ElementType(TocInterchange::class)]
    public readonly array $tocConnectionTimes;
}