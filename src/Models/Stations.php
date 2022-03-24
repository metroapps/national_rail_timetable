<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

class Stations {
    public function __construct(
        array $stationsByCrs
        , array $stationsByName
        , array $stationsByTiploc
    ) {
        $this->stationsByTiploc = $stationsByTiploc;
        $this->stationsByName = $stationsByName;
        $this->stationsByCrs = $stationsByCrs;
    }

    /** @var array<string, Station> */
    public readonly array $stationsByCrs;
    /** @var array<string, Station> */
    public readonly array $stationsByName;
    /** @var array<string, Station> */
    public readonly array $stationsByTiploc;
}