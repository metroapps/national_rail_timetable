<?php
declare(strict_types=1);

namespace App\Enums;

enum TimeType : string {
    case WORKING_ARRIVAL = 'scheduled_arrival_time';
    case PUBLIC_ARRIVAL = 'public_arrival_time';
    case PASS = 'scheduled_pass_time';
    case PUBLIC_DEPARTURE = 'public_departure_time';
    case WORKING_DEPARTURE = 'scheduled_departure_time';

    public function isArrival() : bool {
        return $this === self::PUBLIC_ARRIVAL || $this === self::WORKING_ARRIVAL;
    }

    public function isDeparture() : bool {
        return $this === self::PUBLIC_DEPARTURE || $this === self::WORKING_DEPARTURE;
    }
}
