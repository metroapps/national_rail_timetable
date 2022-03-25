<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use Miklcct\NationalRailJourneyPlanner\Enums\Catering;
use Miklcct\NationalRailJourneyPlanner\Enums\Power;
use Miklcct\NationalRailJourneyPlanner\Enums\Reservation;
use Miklcct\NationalRailJourneyPlanner\Enums\TrainCategory;

class ServiceProperty {
    public function __construct(
        public readonly TrainCategory $trainCategory
        , public readonly string $identity
        , public readonly string $headcode
        , public readonly string $portionId
        , public readonly Power $power
        , public readonly string $timingLoad
        , public readonly ?int $speedMph
        , public readonly bool $doo
        , array $seatingClasses
        , array $sleeperClasses
        , public readonly Reservation $reservation
        , array $caterings
        , public readonly ?string $rsid
    ) {
        $this->sleeperClasses = $sleeperClasses;
        $this->seatingClasses = $seatingClasses;
        $this->caterings = $caterings;
    }

    /** @var Catering[] */
    public readonly array $caterings;
    /** @var array<int, bool> key 1 for first class, key 2 for standard class */
    public readonly array $seatingClasses;
    /** @var array<int, bool> key 1 for first class, key 2 for standard class */
    public readonly array $sleeperClasses;


}