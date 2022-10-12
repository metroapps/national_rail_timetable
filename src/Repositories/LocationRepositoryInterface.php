<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Repositories;

use Miklcct\NationalRailJourneyPlanner\Models\Location;

interface LocationRepositoryInterface {
    public function getLocationByCrs(string $crs) : ?Location;

    public function getLocationByName(string $name) : ?Location;

    public function getLocationByTiploc(string $tiploc) : ?Location;

    /**
     * @param Location[] $locations
     */
    public function insertLocations(array $locations) : void;

    /**
     * @param array<string, string> $aliases
     */
    public function insertAliases(array $aliases) : void;

    /**
     * @return string[]
     */
    public function getAllStationNames() : array;
}