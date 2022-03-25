<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateTimeImmutable;
use DateTimeZone;
use Miklcct\NationalRailJourneyPlanner\Enums\BankHoliday;
use Miklcct\NationalRailJourneyPlanner\Enums\ShortTermPlanning;

abstract class ServiceEntry {
    public function __construct(
        public readonly string $uid
        , public readonly Period $period
        , public readonly BankHoliday $excludeBankHoliday
        , public readonly ShortTermPlanning $shortTermPlanning
    ) {}

    public function runsOnDate(DateTimeImmutable $date) : bool {
        static $timezone = new DateTimeZone('Europe/London');
        $date = $date->setTimezone($timezone);
        return $this->period->isActive($date)
            && !$this->excludeBankHoliday->isActive($date);
    }
}