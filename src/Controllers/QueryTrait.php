<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Controllers;

use Miklcct\NationalRailTimetable\Exceptions\StationNotFound;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Miklcct\NationalRailTimetable\Repositories\LocationRepositoryInterface;

trait QueryTrait {
    private static function getQueryStation(string $name_or_crs, LocationRepositoryInterface $location_repository) : ?LocationWithCrs {
        if ($name_or_crs === '') {
            return null;
        }
        $station = $location_repository->getLocationByCrs($name_or_crs)
            ?? $location_repository->getLocationByName($name_or_crs);
        if (!$station instanceof LocationWithCrs) {
            throw new StationNotFound($name_or_crs);
        }
        return $station;
    }
}