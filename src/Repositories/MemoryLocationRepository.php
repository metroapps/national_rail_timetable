<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Repositories;

use Miklcct\NationalRailJourneyPlanner\Models\Location;
use Miklcct\NationalRailJourneyPlanner\Models\Station;

use function is_string;

class MemoryLocationRepository implements LocationRepositoryInterface {
    public function getLocationByCrs(string $crs) : ?Location {
        return $this->locationsByCrs[$crs] ?? null;
    }

    public function getLocationByName(string $name) : ?Location {
        $result = $this->locationsByName[$name] ?? null;
        return is_string($result) ? $this->getLocationByName($result) : $result;
    }

    public function getLocationByTiploc(string $tiploc) : ?Location {
        return $this->locationsByTiploc[$tiploc] ?? null;
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

    public function getAllStationNames() : array {
        return array_keys(array_filter($this->locationsByName, fn($location) => is_string($location) || $location instanceof Station));
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