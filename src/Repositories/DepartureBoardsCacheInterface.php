<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Repositories;

use DateTimeImmutable;
use Miklcct\NationalRailTimetable\Enums\TimeType;
use Miklcct\NationalRailTimetable\Models\DepartureBoard;

interface DepartureBoardsCacheInterface {
    public function getDepartureBoard(
        string $crs,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        TimeType $time_type
    ) : ?DepartureBoard;

    public function putDepartureBoard(DepartureBoard $departure_board) : void;
}