<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateTimeImmutable;
use DateTimeZone;

class Period {
    public function __construct(
        public readonly DateTimeImmutable $from
        , public readonly DateTimeImmutable $to
        , array $weekdays
    ) {
        $this->weekdays = $weekdays;
    }

    /** @var bool[] 7 bits specifying if it is active on each of the weekdays */
    public readonly array $weekdays;

    public function isActive(DateTimeImmutable $date) : bool {
        static $timezone = new DateTimeZone('Europe/London');
        $date = $date->setTimezone($timezone);
        return $date >= $this->from->setTime(0, 0)
            && $date->setTime(0, 0) <= $this->to
            && $this->weekdays[(int)$date->format('w')];
    }
}