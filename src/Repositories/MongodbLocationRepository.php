<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Repositories;

use Miklcct\NationalRailJourneyPlanner\Models\Location;
use MongoDB\Collection;
use MongoDB\Driver\Cursor;
use function array_keys;
use function array_values;
use function is_array;

class MongodbLocationRepository implements LocationRepositoryInterface {
    public function __construct(private readonly Collection $collection) {
    }

    public function getLocationByCrs(string $crs) : ?Location {
        return $this->crsCache[$crs] ??= $this->processResult($this->collection->find(['$or' => [['crsCode' => $crs], ['minorCrsCode' => $crs]]]));
    }

    public function getLocationByName(string $name) : ?Location {
        return $this->nameCache[$name] ??= $this->processResult($this->collection->find(['name' => $name]));
    }

    public function getLocationByTiploc(string $tiploc) : ?Location {
        return $this->tiplocCache[$tiploc] ??= $this->processResult($this->collection->find(['tiploc' => $tiploc]));
    }

    public function insertLocations(array $locations) : void {
        if ($locations !== []) {
            $this->collection->insertMany($locations);
        }
        $this->clearCache();
    }

    public function insertAliases(array $aliases) : void {
        if ($aliases !== []) {
            $this->collection->insertMany(
                array_map(
                    static function (string $key, string $value) : array {
                        return ['name' => $key, 'alias' => $value];
                    }
                    , array_keys($aliases)
                    , array_values($aliases)
                )
            );
        }
    }

    public function addIndexes() : void {
        $this->collection->createIndexes(
            [
                ['key' => ['tiploc' => 1]],
                ['key' => ['crsCode' => 1]],
                ['key' => ['name' => 'text']],
            ]
        );
    }

    public function clearCache() : void {
        $this->crsCache = [];
        $this->nameCache = [];
        $this->tiplocCache = [];
    }

    private function processResult(Cursor $cursor) : ?Location {
        $result = null;
        foreach ($cursor as $item) {
            if (is_array($item) && isset($item['alias'])) {
                $item = $this->getLocationByName($item['alias']);
            }
            if ($item instanceof Location && $item->isSuperior($result)) {
                $result = $item;
            }
        }
        return $result;
    }

    private array $crsCache = [];
    private array $nameCache = [];
    private array $tiplocCache = [];
}