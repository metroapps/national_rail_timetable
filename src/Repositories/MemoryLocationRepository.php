<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Repositories;

use Miklcct\NationalRailTimetable\Models\Location;
use Miklcct\NationalRailTimetable\Models\Station;

use function is_string;

class MemoryLocationRepository implements LocationRepositoryInterface {
    public function getLocationByCrs(string $crs) : ?Location {
        return $this->locationsByCrs[strtoupper($crs)] ?? null;
    }

    public function getLocationByName(string $name) : ?Location {
        $result = $this->locationsByName[strtoupper($name)] ?? null;
        return is_string($result) ? $this->getLocationByName($result) : $result;
    }

    public function getLocationByTiploc(string $tiploc) : ?Location {
        return $this->locationsByTiploc[strtoupper($tiploc)] ?? null;
    }

    public function insertLocations(array $locations) : void {
        foreach ($locations as $station) {
            if ($station->crsCode !== null) {
                $this->updateStation(
                    $this->locationsByCrs
                    , $station->crsCode
                    , $station
                );
            }
            if (isset($station->minorCrsCode)) {
                $this->updateStation(
                    $this->locationsByCrs
                    , $station->minorCrsCode
                    , $station
                );
            }
            $this->updateStation(
                $this->locationsByName
                , $station->name
                , $station
            );
            $this->updateStation(
                $this->locationsByTiploc
                , $station->tiploc
                , $station
            );
        }
    }

    public function insertAliases(array $aliases) : void {
        $this->locationsByName += $aliases;
    }

    public function getAllStations() : array {
        return $this->locationsByCrs;
    }

    /** @var array<string, Location> */
    private array $locationsByCrs = [];
    /** @var array<string, string|Location> */
    private array $locationsByName = [];
    /** @var array<string, Location> */
    private array $locationsByTiploc = [];

    private function updateStation(
        array &$bucket
        , string $key
        , Location $station
    ) : void {
        $existing = $bucket[$key] ?? null;
        if ($station->isSuperior($existing)) {
            $bucket[$key] = $station;
        }
    }
}