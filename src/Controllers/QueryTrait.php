<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Controllers;

use Miklcct\NationalRailTimetable\Exceptions\StationNotFound;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Miklcct\NationalRailTimetable\Repositories\LocationRepositoryInterface;

trait QueryTrait {
    private function getQueryStation(string $name_or_crs) : ?LocationWithCrs {
        if ($name_or_crs === '') {
            return null;
        }
        $station = $this->locationRepository->getLocationByCrs($name_or_crs)
            ?? $this->locationRepository->getLocationByName($name_or_crs);
        if (!$station instanceof LocationWithCrs) {
            throw new StationNotFound($name_or_crs);
        }
        return $station;
    }

    private readonly LocationRepositoryInterface $locationRepository;
}