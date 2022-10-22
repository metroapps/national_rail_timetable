<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Controllers;

use Miklcct\NationalRailTimetable\Exceptions\StationNotFound;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\Location;
use Miklcct\NationalRailTimetable\Repositories\LocationRepositoryInterface;
use Safe\DateTimeImmutable;

trait QueryTrait {
    private function getStationFromInput(string $station_input) : ?Location {
        if ($station_input === '') {
            return null;
        }
        $station = $this->locationRepository->getLocationByCrs($station_input)
            ?? $this->locationRepository->getLocationByName($station_input);
        if ($station?->crsCode === null) {
            throw new StationNotFound($station_input);
        }
        return $station;
    }

    private function getArrivalMode(array $query) : bool {
        return ($query['mode'] ?? '') === 'arrivals';
    }

    private function getDate(array $query) : Date {
        return Date::fromDateTimeInterface(new DateTimeImmutable(empty($query['date']) ? 'now' : $query['date']));
    }

    private readonly LocationRepositoryInterface $locationRepository;
}