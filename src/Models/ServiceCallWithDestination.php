<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateTimeImmutable;
use Miklcct\NationalRailJourneyPlanner\Attributes\ElementType;
use Miklcct\NationalRailJourneyPlanner\Enums\TimeType;
use Miklcct\NationalRailJourneyPlanner\Models\Points\TimingPoint;

class ServiceCallWithDestination extends ServiceCall {
    use BsonSerializeTrait;

    public function __construct(
        DateTimeImmutable $timestamp
        , TimeType $timeType
        , DatedService $datedService
        , TimingPoint $call
        , ServiceProperty $serviceProperty
        , array $origins
        , array $destinations
    ) {
        parent::__construct($timestamp, $timeType, $datedService, $call, $serviceProperty);
        $this->origins = $origins;
        $this->destinations = $destinations;
    }

    /** @var Location[] */
    #[ElementType(Location::class)]
    public readonly array $origins;
    /** @var Location[] */
    #[ElementType(Location::class)]
    public readonly array $destinations;
}