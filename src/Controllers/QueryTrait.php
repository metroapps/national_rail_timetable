<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Controllers;

use Miklcct\NationalRailTimetable\Exceptions\StationNotFound;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\Location;
use Miklcct\NationalRailTimetable\Repositories\LocationRepositoryInterface;
use Safe\DateTimeImmutable;

trait QueryTrait {
    private function getQueryStation(string $name_or_crs) : ?Location {
        if ($name_or_crs === '') {
            return null;
        }
        $station = $this->locationRepository->getLocationByCrs($name_or_crs)
            ?? $this->locationRepository->getLocationByName($name_or_crs);
        if ($station?->crsCode === null) {
            throw new StationNotFound($name_or_crs);
        }
        return $station;
    }

    private function getQueryArrivalMode(array $query) : bool {
        return ($query['mode'] ?? '') === 'arrivals';
    }

    private function getQueryDate(array $query) : Date {
        return Date::fromDateTimeInterface(new DateTimeImmutable(empty($query['date']) ? 'now' : $query['date']));
    }

    private readonly LocationRepositoryInterface $locationRepository;
}