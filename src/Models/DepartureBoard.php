<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateTimeImmutable;
use InvalidArgumentException;
use Miklcct\NationalRailJourneyPlanner\Attributes\ElementType;
use Miklcct\NationalRailJourneyPlanner\Enums\TimeType;
use MongoDB\BSON\Persistable;

class DepartureBoard implements Persistable {
    use BsonSerializeTrait;

    public function __construct(
        public readonly string $crs
        , public readonly DateTimeImmutable $from
        , public readonly DateTimeImmutable $to
        , public readonly TimeType $timeType
        , array $calls
    ) {
        foreach ($calls as $call) {
            if (!$call instanceof ServiceCallWithDestination) {
                throw new InvalidArgumentException('Calls must be ServiceCallWithDestination');
            }
        }
        $this->calls = $calls;
    }

    /** @var ServiceCallWithDestination[] */
    #[ElementType(ServiceCallWithDestination::class)]
    public readonly array $calls;
}