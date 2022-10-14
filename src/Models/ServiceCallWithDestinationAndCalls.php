<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Models;

use DateTimeImmutable;
use Miklcct\NationalRailTimetable\Attributes\ElementType;
use Miklcct\NationalRailTimetable\Enums\Mode;
use Miklcct\NationalRailTimetable\Enums\TimeType;
use Miklcct\NationalRailTimetable\Models\Points\TimingPoint;

class ServiceCallWithDestinationAndCalls extends ServiceCallWithDestination {
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
        , array $precedingCalls
        , array $subsequentCalls
    ) {
        parent::__construct($timestamp, $timeType, $uid, $date, $call, $mode, $toc, $serviceProperty, $origins, $destinations);
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