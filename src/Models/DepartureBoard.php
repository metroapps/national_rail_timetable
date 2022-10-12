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

    public function filter(array $crses, TimeType $time_type) : static {
        return new static(
            $this->crs
            , $this->from
            , $this->to
            , $this->timeType
            , array_filter(
                $this->calls
                , function (ServiceCallWithDestinationAndCalls $service_call) use ($time_type, $crses) : bool {
                    return !in_array($this->timeType, [TimeType::PUBLIC_ARRIVAL, TimeType::WORKING_ARRIVAL], true)
                            && array_filter(
                                $service_call->subsequentCalls
                                , static function (ServiceCallWithDestination $filter_call) use ($time_type, $crses) : bool {
                                    return in_array($filter_call->call->location->crsCode, $crses, true);
                                }
                            ) !== []
                        || !in_array($this->timeType, [TimeType::PUBLIC_DEPARTURE, TimeType::WORKING_DEPARTURE], true)
                            && array_filter(
                                $service_call->precedingCalls
                                , static function (ServiceCallWithDestination $filter_call) use ($time_type, $crses) : bool {
                                    return in_array($filter_call->call->location->crsCode, $crses, true);
                                }
                            ) !== [];
                    }
            )
        );
    }

    /** @var ServiceCallWithDestinationAndCalls[] */
    #[ElementType(ServiceCallWithDestinationAndCalls::class)]
    public readonly array $calls;
}