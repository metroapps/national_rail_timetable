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

    public function isOvertaken(ServiceCallWithDestinationAndCalls $service_call, string $destination_crs, string $portion_uid) : bool {
        if (in_array($this->timeType, [TimeType::WORKING_DEPARTURE, TimeType::PUBLIC_DEPARTURE])) {
            return array_filter(
                $service_call->subsequentCalls
                , fn(ServiceCallWithDestination $filter_call) : bool =>
                    in_array($portion_uid, array_keys($filter_call->destinations), true)
                    && $filter_call->call->location->crsCode === $destination_crs
            ) === []
            || array_filter(
                $service_call->subsequentCalls
                , fn(ServiceCallWithDestination $filter_call) : bool =>
                    in_array($portion_uid, array_keys($filter_call->destinations), true)
                    && $filter_call->call->location->crsCode === $destination_crs 
                    && array_filter(
                        $this->calls
                        , static fn(ServiceCallWithDestinationAndCalls $other_call) : bool =>
                            $other_call->timestamp >= $service_call->timestamp
                            && array_filter(
                                $other_call->subsequentCalls
                                , static fn(ServiceCallWithDestination $compare_filter_call) : bool =>
                                    $compare_filter_call->call->location->crsCode === $destination_crs
                                    && $compare_filter_call->timestamp < $filter_call->timestamp
                            ) !== []
                    ) !== []
            ) !== [];
        }
        if (in_array($this->timeType, [TimeType::WORKING_ARRIVAL, TimeType::PUBLIC_ARRIVAL])) {
            return array_filter(
                $service_call->precedingCalls
                , fn(ServiceCallWithDestination $filter_call) : bool =>
                    in_array($portion_uid, array_keys($filter_call->destinations), true)
                    && $filter_call->call->location->crsCode === $destination_crs
            ) === []
            || array_filter(
                $service_call->precedingCalls
                , fn(ServiceCallWithDestination $filter_call) : bool =>
                    $filter_call->call->location->crsCode === $destination_crs && array_filter(
                        $this->calls
                        , static fn(ServiceCallWithDestinationAndCalls $other_call) : bool =>
                            $other_call->timestamp <= $service_call->timestamp
                            && array_filter(
                                $other_call->precedingCalls
                                , static fn(ServiceCallWithDestination $compare_filter_call) : bool =>
                                    $compare_filter_call->call->location->crsCode === $destination_crs
                                    && $compare_filter_call->timestamp > $filter_call->timestamp
                            ) !== []
                    ) !== []
            ) !== [];
        }
        return false;
    }

    public function filterByDestination(string $filter_crs) : static {
        return new static(
            $this->crs
            , $this->from
            , $this->to
            , $this->timeType
            , array_filter(
                $this->calls
                , fn(ServiceCallWithDestinationAndCalls $service_call) : bool =>
                    !in_array($this->timeType, [TimeType::PUBLIC_ARRIVAL, TimeType::WORKING_ARRIVAL], true)
                        && array_filter(
                            $service_call->subsequentCalls
                            , fn(ServiceCallWithDestination $filter_call) : bool =>
                                $filter_call->call->location->crsCode === $filter_crs
                        ) !== []
                    || !in_array($this->timeType, [TimeType::PUBLIC_DEPARTURE, TimeType::WORKING_DEPARTURE], true)
                        && array_filter(
                            $service_call->precedingCalls
                            , fn(ServiceCallWithDestination $filter_call) : bool =>
                                $filter_call->call->location->crsCode === $filter_crs
                    ) !== []
            )
        );
    }

    /** @var ServiceCallWithDestinationAndCalls[] */
    #[ElementType(ServiceCallWithDestinationAndCalls::class)]
    public readonly array $calls;
}