<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateTimeImmutable;
use Miklcct\NationalRailJourneyPlanner\Attributes\ElementType;
use Miklcct\NationalRailJourneyPlanner\Enums\TimeType;
use Miklcct\NationalRailJourneyPlanner\Models\Points\DestinationPoint;
use Miklcct\NationalRailJourneyPlanner\Models\Points\OriginPoint;
use Miklcct\NationalRailJourneyPlanner\Models\Points\TimingPoint;

// This class can be used to identify which portion(s) of the train will call
class ServiceCallWithDestination extends ServiceCall {
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
    ) {
        parent::__construct($timestamp, $timeType, $uid, $date, $call, $serviceProperty);
        $this->origins = $origins;
        $this->destinations = $destinations;
    }

    /** @var OriginPoint[] */
    #[ElementType(OriginPoint::class)]
    public readonly array $origins;
    /** @var DestinationPoint[] */
    #[ElementType(DestinationPoint::class)]
    public readonly array $destinations;
}