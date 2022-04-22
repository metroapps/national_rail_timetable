<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use Miklcct\NationalRailJourneyPlanner\Attributes\ElementType;
use MongoDB\BSON\Persistable;

class Period implements Persistable {
    use BsonSerializeTrait;

    public function __construct(
        public readonly Date $from
        , public readonly Date $to
        , array $weekdays
    ) {
        $this->weekdays = $weekdays;
    }

    /** @var bool[] 7 bits specifying if it is active on each of the weekdays */
    #[ElementType('bool')]
    public readonly array $weekdays;

    public function isActive(Date $date) : bool {
        return $date->toDateTimeImmutable() >= $this->from->toDateTimeImmutable()
            && $date->toDateTimeImmutable() <= $this->to->toDateTimeImmutable()
            && $this->weekdays[$date->getWeekday()];
    }
}