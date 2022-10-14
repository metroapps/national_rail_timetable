<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Models;

use Miklcct\NationalRailTimetable\Repositories\LocationRepositoryInterface;
use MongoDB\BSON\Persistable;
use function is_string;
use function Safe\preg_replace;

class Location implements Persistable {
    use BsonSerializeTrait;

    public function __construct(
        public readonly string $tiploc
        , public readonly ?string $crsCode
        , public readonly string $name
    ) {}

    public function isSuperior(Location|string|null $existing) : bool {
        return !is_string($existing) && (
            $existing === null || $this->superiorScore() > $existing->superiorScore()
        );
    }

    public function promoteToStation(LocationRepositoryInterface $repository) : ?Station {
        if ($this->crsCode === null) {
            return null;
        }
        $station = $repository->getLocationByCrs($this->crsCode);
        if (!$station instanceof Station) {
            return null;
        }
        return new Station(
            $this->tiploc
            , $station->crsCode
            , $station->name
            , $station->minorCrsCode
            , $station->interchange
            , $station->easting
            , $station->northing
            , $station->minimumConnectionTime
            , $station->tocConnectionTimes
        );
    }

    public function getShortName() : string {
        return preg_replace('/ \(.*\)$/', '', $this->name);
    }

    private function superiorScore() : int {
        return $this instanceof Station
            ? $this->interchange !== 9 ? 3 : ($this->minorCrsCode === $this->crsCode ? 2 : 1)
            : 0;
    }
}