<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Models;

use DateTimeImmutable;
use InvalidArgumentException;
use Miklcct\NationalRailTimetable\Enums\TimeType;
use MongoDB\BSON\Persistable;

class DepartureBoard implements Persistable {
    use BsonSerializeTrait;

    /**
     * @param string $crs
     * @param DateTimeImmutable $from
     * @param DateTimeImmutable $to
     * @param TimeType $timeType
     * @param ServiceCallWithDestinationAndCalls[] $calls
     */
    public function __construct(
        public readonly string $crs
        , public readonly DateTimeImmutable $from
        , public readonly DateTimeImmutable $to
        , public readonly TimeType $timeType
        , public readonly array $calls
    ) {
        foreach ($calls as $call) {
            if (!$call instanceof ServiceCallWithDestinationAndCalls) {
                throw new InvalidArgumentException('Calls must be ServiceCallWithDestinationAndCalls');
            }
        }
    }

    public function isOvertaken(ServiceCallWithDestinationAndCalls $service_call, string $destination_crs, string $portion_uid) : bool {
        if (in_array($this->timeType, [TimeType::WORKING_DEPARTURE, TimeType::PUBLIC_DEPARTURE], true)) {
            return array_filter(
                $service_call->subsequentCalls
                , fn(ServiceCallWithDestination $filter_call) : bool =>
                    array_key_exists($portion_uid, $filter_call->destinations)
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
                    ) === []
            ) === [];
        }
        if (in_array($this->timeType, [TimeType::WORKING_ARRIVAL, TimeType::PUBLIC_ARRIVAL], true)) {
            return array_filter(
                $service_call->precedingCalls
                , fn(ServiceCallWithDestination $filter_call) : bool =>
                    array_key_exists($portion_uid, $filter_call->origins)
                    && $filter_call->call->location->crsCode === $destination_crs 
                    && array_filter(
                        $this->calls
                        , static fn(ServiceCallWithDestinationAndCalls $other_call) : bool =>
                            $other_call->timestamp <= $service_call->timestamp
                            && array_filter(
                                $other_call->precedingCalls
                                , static fn(ServiceCallWithDestination $compare_filter_call) : bool =>
                                    $compare_filter_call->call->location->crsCode === $destination_crs
                                    && $compare_filter_call->timestamp > $filter_call->timestamp
                            ) !== []
                    ) === []
            ) === [];
        }
        return false;
    }

    /**
     * Filter the departure board by preceding / subsequent calls
     *
     * @param string|string[] $filter_crs
     * @param bool $truncate
     * @return static
     */
    public function filterByDestination(array|string $filter_crs, bool $truncate = false) : static {
        if (!is_array($filter_crs)) {
            $filter_crs = (array)$filter_crs;
        }
        return new static(
            $this->crs
            , $this->from
            , $this->to
            , $this->timeType
            , array_values(
                array_map(
                    function (ServiceCallWithDestinationAndCalls $service_call) use ($filter_crs, $truncate): ServiceCallWithDestinationAndCalls {
                        if (!in_array($this->timeType, [TimeType::PUBLIC_ARRIVAL, TimeType::WORKING_ARRIVAL], true)) {
                            $destinations = array_merge(
                                ...array_map(
                                    static fn(ServiceCallWithDestination $filter_call) => $filter_call->destinations
                                    , array_filter(
                                        $service_call->subsequentCalls
                                        , static fn(ServiceCallWithDestination $filter_call) : bool =>
                                            in_array($filter_call->call->location->crsCode, $filter_crs, true)
                                    )
                                )
                            );
                            $subsequentCalls = array_filter(
                                $service_call->subsequentCalls
                                , static fn(ServiceCallWithDestination $filter_call, int $offset) : bool =>
                                    array_intersect_key($destinations, $filter_call->destinations) !== []
                                    && (!$truncate || array_filter(
                                        array_slice($service_call->subsequentCalls, $offset, null)
                                        , static fn(ServiceCallWithDestination $truncate_call) : bool =>
                                            array_intersect_key($truncate_call->destinations, $filter_call->destinations) !== []
                                            && in_array($truncate_call->call->location->crsCode, $filter_crs, true)
                                    ) !== [])
                                , ARRAY_FILTER_USE_BOTH
                            );
                        } else {
                            $destinations = $service_call->destinations;
                            $subsequentCalls = $service_call->subsequentCalls;
                        }
                        if (!in_array($this->timeType, [TimeType::PUBLIC_DEPARTURE, TimeType::WORKING_DEPARTURE], true)) {
                            $origins = array_merge(
                                ...array_map(
                                    static fn(ServiceCallWithDestination $filter_call) => $filter_call->origins
                                    , array_filter(
                                        $service_call->precedingCalls
                                        , static fn(ServiceCallWithDestination $filter_call) : bool =>
                                            in_array($filter_call->call->location->crsCode, $filter_crs, true)
                                    )
                                )
                            );
                            $precedingCalls = array_filter(
                                $service_call->precedingCalls
                                , static fn(ServiceCallWithDestination $filter_call, int $offset) : bool =>
                                    array_intersect_key($origins, $filter_call->origins) !== []
                                    && (!$truncate || array_filter(
                                        array_slice($service_call->precedingCalls, 0, $offset + 1)
                                        , static fn(ServiceCallWithDestination $truncate_call) : bool =>
                                            array_intersect_key($truncate_call->origins, $filter_call->origins) !== []
                                            && in_array($truncate_call->call->location->crsCode, $filter_crs, true)
                                    ) !== [])
                                , ARRAY_FILTER_USE_BOTH
                            );
                        } else {
                            $origins = $service_call->origins;
                            $precedingCalls = $service_call->precedingCalls;
                        }
                        return new ServiceCallWithDestinationAndCalls(
                            $service_call->timestamp
                            , $service_call->timeType
                            , $service_call->uid
                            , $service_call->date
                            , $service_call->call
                            , $service_call->mode
                            , $service_call->toc
                            , $service_call->serviceProperty
                            , $origins
                            , $destinations
                            , $precedingCalls
                            , $subsequentCalls
                        );
                    }
                    , array_filter(
                        $this->calls
                        , fn(ServiceCallWithDestinationAndCalls $service_call) : bool =>
                            !in_array($this->timeType, [TimeType::PUBLIC_ARRIVAL, TimeType::WORKING_ARRIVAL], true)
                                && array_filter(
                                    $service_call->subsequentCalls
                                    , static fn(ServiceCallWithDestination $filter_call) : bool =>
                                        in_array($filter_call->call->location->crsCode, $filter_crs, true)
                                ) !== []
                            || !in_array($this->timeType, [TimeType::PUBLIC_DEPARTURE, TimeType::WORKING_DEPARTURE], true)
                                && array_filter(
                                    $service_call->precedingCalls
                                    , static fn(ServiceCallWithDestination $filter_call) : bool =>
                                        in_array($filter_call->call->location->crsCode, $filter_crs, true)
                            ) !== []
                    )
                )
            )
        );
    }

    /**
     * Group the services into sets which don't share calls.
     *
     * @return static[]
     */
    public function groupServices() : array {
        $station_groups = [];
        $result = [];
        foreach ($this->calls as $call) {
            $group_id = $station_groups === [] ? 0 : max($station_groups) + 1;
            foreach ($call->subsequentCalls as $subsequent_call) {
                $subsequent_crs = $subsequent_call->call->location->crsCode;
                if (isset($station_groups[$subsequent_crs])) {
                    $group_to_be_joined = $station_groups[$subsequent_crs];
                    if ($group_to_be_joined !== $group_id) {
                        foreach ($station_groups as &$station_group) {
                            if ($station_group === $group_id) {
                                $station_group = $group_to_be_joined;
                            }
                        }
                        unset($station_group);
                    }
                    $group_id = $group_to_be_joined;
                } else {
                    $station_groups[$subsequent_crs] = $group_id;
                }
            }
            $result[$group_id][] = $call;
        }
        return array_map(
            fn(array $calls) => new static($this->crs, $this->from, $this->to, $this->timeType, $calls)
            , $result
        );
    }

    /**
     * @return Location[]
     */
    public function getLocationsOfFirstCall() : array {
        $result = [];
        foreach ($this->calls as $service_call) {
            $first_call = $this->timeType->isArrival()
                ? $service_call->precedingCalls[count($service_call->precedingCalls) - 1]
                : $service_call->subsequentCalls[0];
            $call_location = $first_call->call->location;
            if (array_filter($result, static fn(Location $location) => $location->crsCode === $call_location->crsCode) === []) {
                $result[] = $call_location;
            }
        }
        return $result;
    }

    /**
     * @return Location[]
     */
    public function getDestinations() : array {
        $result = [];
        foreach ($this->calls as $service_call) {
            foreach ($service_call->destinations as $destination_point) {
                $destination = $destination_point->location;
                if (
                    array_filter($result, static fn(Location $location) => $location->crsCode === $destination->crsCode) === []
                    && array_filter(
                        $this->calls
                        , static fn(ServiceCallWithDestinationAndCalls $compare_call)
                            => array_filter(
                                $compare_call->subsequentCalls
                                , static fn(ServiceCallWithDestination $compare_subsequent_call)
                                    => $compare_subsequent_call->call->location->crsCode === $destination->crsCode
                                        && array_filter(
                                            $compare_call->subsequentCalls
                                            , static fn(ServiceCallWithDestination $compare_filter_call)
                                                => $compare_filter_call->isInSamePortion($compare_subsequent_call)
                                                    && $compare_filter_call->timestamp > $compare_subsequent_call->timestamp
                                        ) !== []
                            ) !== []
                    ) === []
                ) {
                    $result[] = $destination;
                }
            }
        }
        return $result;
    }
}