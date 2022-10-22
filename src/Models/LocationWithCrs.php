<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Models;

use Miklcct\NationalRailTimetable\Repositories\LocationRepositoryInterface;

interface LocationWithCrs {
    public function getCrsCode() : string;

    public function promoteToStation(LocationRepositoryInterface $location_repository) : ?Station;
}