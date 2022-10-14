<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Models;

use Miklcct\NationalRailTimetable\Enums\BankHoliday;
use Miklcct\NationalRailTimetable\Enums\ShortTermPlanning;
use MongoDB\BSON\Persistable;

class ServiceEntry implements Persistable {
    use BsonSerializeTrait;
    use OverlayTrait;

    public function __construct(
        public readonly string $uid
        , public readonly Period $period
        , public readonly BankHoliday $excludeBankHoliday
        , public readonly ShortTermPlanning $shortTermPlanning
    ) {}

    public function runsOnDate(Date $date) : bool {
        return $this->period->isActive($date)
            && !$this->excludeBankHoliday->isActive($date);
    }
}