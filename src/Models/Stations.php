<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

class Stations {
    /**
     * @var Location[] $stations
     * @var array $aliases
     */
    public function __construct(
        private readonly array $stations
        , private readonly array $aliases
    ) {
        $stationsByCrs = [];
        $stationsByName = [];
        $stationsByTiploc = [];
        foreach ($stations as $station) {
            if ($station->crsCode !== null) {
                $this->updateStation(
                    $stationsByCrs
                    , $station->crsCode
                    , $station
                );
            }
            $this->updateStation(
                $stationsByName
                , $station->name
                , $station
            );
            $this->updateStation(
                $stationsByTiploc
                , $station->tiploc
                , $station
            );
            foreach ($aliases as $alias => $name) {
                if (isset($this->stationsByName[$name])) {
                    $stationsByName[$alias] = $stationsByName[$name];
                }
            }
        }
        $this->stationsByCrs = $stationsByCrs;
        $this->stationsByName = $stationsByName;
        $this->stationsByTiploc = $stationsByTiploc;
    }

    /** @var array<string, Location> */
    public readonly array $stationsByCrs;
    /** @var array<string, Location> */
    public readonly array $stationsByName;
    /** @var array<string, Location> */
    public readonly array $stationsByTiploc;

    private function updateStation(
        array &$bucket
        , string $key
        , Location $station
    ) : void {
        $existing = $bucket[$key] ?? null;
        if (
            $existing === null
            || $station instanceof Station && (
                !$existing instanceof Station
                || $station->minorCrsCode === $station->crsCode
                    && $existing->minorCrsCode !== $existing->crsCode
                || $station->interchange !== 9 && $existing->interchange === 9
            )
        ) {
            $bucket[$key] = $station;
        }
    }

    public function merge(Stations $other) : self {
        return new static(
            array_merge($this->stations, $other->stations)
            , $this->aliases + $other->aliases
        );
    }
}