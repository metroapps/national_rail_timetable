<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Repositories;

use Miklcct\NationalRailTimetable\Models\Location;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;

interface LocationRepositoryInterface {
    public function getLocationByCrs(string $crs) : ?LocationWithCrs /*?(Location&LocationWithCrs)*/;

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
     * @return LocationWithCrs[]
     */
    public function getAllStations() : array;
}