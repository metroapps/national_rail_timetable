<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateTimeImmutable;
use Miklcct\NationalRailJourneyPlanner\Attributes\ElementType;
use Miklcct\NationalRailJourneyPlanner\Enums\TimeType;
use Miklcct\NationalRailJourneyPlanner\Models\Points\TimingPoint;

class ServiceCallWithDestinationAndCalls extends ServiceCallWithDestination {
    use BsonSerializeTrait;

    public function __construct(
        DateTimeImmutable $timestamp
        , TimeType $timeType
        , string $uid
        , Date $date
        , TimingPoint $call
        , ServiceProperty $serviceProperty
        , array $origins
        , array $destinations
        , array $precedingCalls
        , array $subsequentCalls
    ) {
        parent::__construct($timestamp, $timeType, $uid, $date, $call, $serviceProperty, $origins, $destinations);
        $this->precedingCalls = $precedingCalls;
        $this->subsequentCalls = $subsequentCalls;
    }

    /** @var ServiceCallWithDestination[] */
    #[ElementType(ServiceCallWithDestination::class)]
    public array $precedingCalls;
    /** @var ServiceCallWithDestination[] */
    #[ElementType(ServiceCallWithDestination::class)]
    public array $subsequentCalls;
}