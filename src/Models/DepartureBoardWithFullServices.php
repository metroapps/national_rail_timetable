<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateTimeImmutable;
use http\Exception\InvalidArgumentException;
use Miklcct\NationalRailJourneyPlanner\Enums\TimeType;

class DepartureBoardWithFullServices extends DepartureBoard {
    use BsonSerializeTrait;

    public function __construct(
        string $crs,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        TimeType $timeType,
        array $calls
    ) {
        parent::__construct($crs, $from, $to, $timeType, $calls);
        foreach ($this->calls as $call) {
            if (!$call->datedService instanceof FullService) {
                throw new InvalidArgumentException('The DatedService within DepartureBoardWithFullServices must be a FullService.');
            }
        }
    }
}