<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use Miklcct\NationalRailJourneyPlanner\Attributes\ElementType;
use MongoDB\BSON\Persistable;

class FixedLink implements Persistable {
    use BsonSerializeTrait;

    public function __construct(
        public readonly string $mode
        , public readonly Station $origin
        , public readonly Station $destination
        , public readonly int $transferTime
        , public readonly Time $startTime
        , public readonly Time $endTime
        , public readonly int $priority
        , public readonly ?Date $startDate
        , public readonly ?Date $endDate
        , ?array $weekdays
    ) {
        $this->weekdays = $weekdays;
    }

    /** @var bool[] 7 bits specifying if it is active on each of the weekdays */
    #[ElementType('bool')]
    public readonly array $weekdays;
}