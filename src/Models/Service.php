<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use Miklcct\NationalRailJourneyPlanner\Enums\BankHoliday;
use Miklcct\NationalRailJourneyPlanner\Enums\ShortTermPlanning;

class Service extends ServiceEntry {
    public function __construct(
        string $uid
        , Period $period
        , BankHoliday $excludeBankHoliday
        , public readonly string $toc
        , public readonly ServiceProperty $serviceProperty
        , array $timingPoints
        , ShortTermPlanning $shortTermPlanning
    ) {
        parent::__construct(
            $uid
            , $period
            , $excludeBankHoliday
            , $shortTermPlanning
        );
        $this->points = $timingPoints;
    }

    /** @var TimingPoint[] */
    public readonly array $points;
}