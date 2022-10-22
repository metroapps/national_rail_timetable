<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Models;

use DateTimeImmutable;
use Miklcct\NationalRailTimetable\Attributes\ElementType;
use Miklcct\NationalRailTimetable\Enums\Mode;
use Miklcct\NationalRailTimetable\Enums\TimeType;
use Miklcct\NationalRailTimetable\Models\Points\DestinationPoint;
use Miklcct\NationalRailTimetable\Models\Points\OriginPoint;
use Miklcct\NationalRailTimetable\Models\Points\TimingPoint;

// This class can be used to identify which portion(s) of the train will call
class ServiceCallWithDestination extends ServiceCall {
    use BsonSerializeTrait;

    public function __construct(
        DateTimeImmutable $timestamp
        , TimeType $timeType
        , string $uid
        , Date $date
        , TimingPoint $call
        , Mode $mode
        , string $toc
        , ServiceProperty $serviceProperty
        , array $origins
        , array $destinations
    ) {
        parent::__construct($timestamp, $timeType, $uid, $date, $call, $mode, $toc, $serviceProperty);
        $this->origins = $origins;
        $this->destinations = $destinations;
    }

    public function isInSamePortion(self $other) : bool {
        return array_intersect_key($this->origins, $other->origins) !== []
            && array_intersect_key($this->destinations, $other->destinations) !== [];
    }

    /** @var OriginPoint[] */
    #[ElementType(OriginPoint::class)]
    public readonly array $origins;
    /** @var DestinationPoint[] */
    #[ElementType(DestinationPoint::class)]
    public readonly array $destinations;
}