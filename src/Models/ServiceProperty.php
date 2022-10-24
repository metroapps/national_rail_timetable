<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Models;

use Miklcct\NationalRailTimetable\Attributes\ElementType;
use Miklcct\NationalRailTimetable\Enums\Catering;
use Miklcct\NationalRailTimetable\Enums\Power;
use Miklcct\NationalRailTimetable\Enums\Reservation;
use Miklcct\NationalRailTimetable\Enums\TrainCategory;
use MongoDB\BSON\Persistable;
use function substr;

class ServiceProperty implements Persistable {
    use BsonSerializeTrait;

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



    public function getPortions() : ?array {
        $result = [];
        $portion_bitmask = (int)substr($this->rsid, 6, 2);
        for ($bit = 0; 1 << $bit <= 99; ++$bit) {
            if ($portion_bitmask & 1 << $bit) {
                $result[] = $bit + 1;
            }
        }
        return $result === [] ? null : $result;
    }

    public function showIcons() : string {
        $result = '';
        foreach ($this->caterings as $catering) {
            $result .= $catering->showIcon();
        }
        $result .= $this->reservation->showIcon();
        if ($this->seatingClasses[1]) {
            $result .= '<img class="facility" src="/images/first_class.png" alt="first class" title="First class available" />';
        }
        if (array_filter($this->sleeperClasses)) {
            $result .= '<img class="facility" src="/images/sleeper.png" alt="sleeper" title="Sleeper available" />';
        }
        return $result;
    }

    /** @var Catering[] */
    #[ElementType(Catering::class)]
    public readonly array $caterings;
    /** @var array<int, bool> key 1 for first class, key 2 for standard class */
    public readonly array $seatingClasses;
    /** @var array<int, bool> key 1 for first class, key 2 for standard class */
    public readonly array $sleeperClasses;
}