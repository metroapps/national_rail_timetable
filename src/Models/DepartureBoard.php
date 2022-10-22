<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Models;

use DateTimeImmutable;
use InvalidArgumentException;
use Miklcct\NationalRailTimetable\Enums\TimeType;
use MongoDB\BSON\Persistable;
use function array_filter;
use function array_merge;

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
                , function (ServiceCallWithDestination $filter_call) use (
                    $portion_uid,
                    $destination_crs,
                    $service_call
                ) : bool {
                    $location = $filter_call->call->location;
                    return $location instanceof LocationWithCrs
                        && $location->getCrsCode() === $destination_crs
                        && array_key_exists($portion_uid, $filter_call->destinations)
                        && array_filter(
                            $this->calls
                            , static fn(ServiceCallWithDestinationAndCalls $other_call) : bool => $other_call->timestamp
                            >= $service_call->timestamp
                            && array_filter(
                                $other_call->subsequentCalls
                                , static function (ServiceCallWithDestination $compare_filter_call) use ($destination_crs, $filter_call) : bool {
                                    $location = $compare_filter_call->call->location;
                                    return $location instanceof LocationWithCrs
                                        && $location->getCrsCode() === $destination_crs
                                        && $compare_filter_call->timestamp < $filter_call->timestamp;
                                }
                            ) !== []
                        ) === [];
                }
            ) === [];
        }
        if (in_array($this->timeType, [TimeType::WORKING_ARRIVAL, TimeType::PUBLIC_ARRIVAL], true)) {
            return array_filter(
                $service_call->precedingCalls
                , function (ServiceCallWithDestination $filter_call) use (
                    $portion_uid,
                    $destination_crs,
                    $service_call
                ) : bool {
                    $location = $filter_call->call->location;
                    return array_key_exists($portion_uid, $filter_call->origins)
                        && $location instanceof LocationWithCrs && $location->getCrsCode() === $destination_crs
                        && array_filter(
                            $this->calls
                            , static fn(ServiceCallWithDestinationAndCalls $other_call) : bool =>
                                $other_call->timestamp <= $service_call->timestamp
                                && array_filter(
                                    $other_call->precedingCalls
                                    , static function (ServiceCallWithDestination $compare_filter_call) use (
                                    $destination_crs,
                                    $filter_call
                                ) : bool {
                                    $location = $compare_filter_call->call->location;
                                    return $location instanceof LocationWithCrs
                                        && $location->getCrsCode() === $destination_crs
                                        && $compare_filter_call->timestamp > $filter_call->timestamp;
                                }
                            ) !== []
                        ) === [];
                }
            ) === [];
        }
        return false;
    }

    /**
     * Filter the departure board by preceding / subsequent calls
     *
     * @param string[] $filter_crs
     * @param bool $truncate
     * @return static
     */
    public function filterByDestination(array $filter_crs, bool $truncate = false) : static {
        $filter = static function (ServiceCallWithDestination $filter_call) use ($filter_crs) : bool {
            $location = $filter_call->call->location;
            return $location instanceof LocationWithCrs && in_array($location->getCrsCode(), $filter_crs, true);
        };
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
                                        , static function (ServiceCallWithDestination $filter_call) use ($filter_crs) : bool {
                                            $location = $filter_call->call->location;
                                            return $location instanceof LocationWithCrs && in_array($location->getCrsCode(), $filter_crs, true) ;
                                        }
                                    )
                                )
                            );
                            $subsequentCalls = array_filter(
                                $service_call->subsequentCalls
                                , static fn(ServiceCallWithDestination $filter_call, int $offset) : bool =>
                                    array_intersect_key($destinations, $filter_call->destinations) !== []
                                    && (!$truncate || array_filter(
                                        array_slice($service_call->subsequentCalls, $offset, null)
                                        , static function (ServiceCallWithDestination $truncate_call) use (
                                            $filter_call,
                                            $filter_crs
                                        ) : bool {
                                            $location = $truncate_call->call->location;
                                            return $location instanceof LocationWithCrs
                                                && array_intersect_key(
                                                    $truncate_call->destinations,
                                                    $filter_call->destinations
                                                ) !== []
                                                && in_array($location->getCrsCode(), $filter_crs, true);
                                        }
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
                                        , static function (ServiceCallWithDestination $filter_call) use ($filter_crs) : bool {
                                            $location = $filter_call->call->location;
                                            return $location instanceof LocationWithCrs && in_array($location->getCrsCode(), $filter_crs, true);
                                        }
                                    )
                                )
                            );
                            $precedingCalls = array_filter(
                                $service_call->precedingCalls
                                , static fn(ServiceCallWithDestination $filter_call, int $offset) : bool =>
                                    array_intersect_key($origins, $filter_call->origins) !== []
                                    && (!$truncate || array_filter(
                                        array_slice($service_call->precedingCalls, 0, $offset + 1)
                                        , static function (ServiceCallWithDestination $truncate_call) use (
                                            $filter_call,
                                            $filter_crs
                                        ) : bool {
                                            $location = $truncate_call->call->location;
                                            return $location instanceof LocationWithCrs
                                                && array_intersect_key($truncate_call->origins, $filter_call->origins) !== []
                                                && in_array($location->getCrsCode(), $filter_crs, true);
                                        }
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
                                && array_filter($service_call->subsequentCalls, $filter) !== []
                            || !in_array($this->timeType, [TimeType::PUBLIC_DEPARTURE, TimeType::WORKING_DEPARTURE], true)
                                && array_filter($service_call->precedingCalls, $filter) !== []
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
            foreach ($this->timeType->isArrival() ? $call->precedingCalls : $call->subsequentCalls as $subsequent_call) {
                $location = $subsequent_call->call->location;
                if ($location instanceof LocationWithCrs) {
                    $subsequent_crs = $location->getCrsCode();
                    if (isset($station_groups[$subsequent_crs])) {
                        $group_to_be_joined = $station_groups[$subsequent_crs];
                        if ($group_to_be_joined !== $group_id) {
                            foreach ($station_groups as &$station_group) {
                                if ($station_group === $group_id) {
                                    $station_group = $group_to_be_joined;
                                }
                            }
                            unset($station_group);
                            $result[$group_to_be_joined] = array_merge($result[$group_to_be_joined], $result[$group_id] ?? []);
                            unset($result[$group_id]);
                            $group_id = $group_to_be_joined;
                        }
                    } else {
                        $station_groups[$subsequent_crs] = $group_id;
                    }
                }
            }
            $result[$group_id][] = $call;
        }
        foreach ($result as &$group) {
            usort($group, static fn(ServiceCallWithDestinationAndCalls $a, ServiceCallWithDestinationAndCalls $b) => $a->timestamp <=> $b->timestamp);
        }
        unset($group);
        return array_map(
            fn(array $calls) => new static($this->crs, $this->from, $this->to, $this->timeType, $calls)
            , $result
        );
    }

    /**
     * @return LocationWithCrs[]
     */
    public function getDestinations(bool $via = false) : array {
        $all_locations = [];
        $arrival_mode = $this->timeType->isArrival();
        foreach ($this->calls as $service_call) {
            foreach ($arrival_mode ? array_reverse($service_call->precedingCalls) : $service_call->subsequentCalls as $subsequent_call) {
                $call_location = $subsequent_call->call->location;
                if (
                    $call_location instanceof LocationWithCrs
                    && array_filter($all_locations, static fn(LocationWithCrs $location) => $location->getCrsCode() === $call_location->getCrsCode()) === []
                ) {
                    $all_locations[] = $call_location;
                }
            }
        }
        $result = [];
        foreach ($all_locations as $call_location) {
            if (
                array_filter(
                    $this->calls
                    , static fn(ServiceCallWithDestinationAndCalls $compare_call)
                        => $via
                        ? array_filter(
                            $arrival_mode ? $compare_call->precedingCalls : $compare_call->subsequentCalls
                            , static function (ServiceCallWithDestination $compare_subsequent_call) use ($call_location) {
                                $location = $compare_subsequent_call->call->location;
                                return $location instanceof LocationWithCrs
                                    && $location->getCrsCode()
                                    === $call_location->getCrsCode();
                            }
                        ) === []
                        : array_filter(
                            $arrival_mode ? $compare_call->precedingCalls : $compare_call->subsequentCalls
                            , static function (ServiceCallWithDestination $compare_subsequent_call) use ($arrival_mode, $call_location, $compare_call) {
                                $location = $compare_subsequent_call->call->location;
                                return $location instanceof LocationWithCrs && $location->getCrsCode() === $call_location->getCrsCode()
                                    && array_filter(
                                        $arrival_mode ? $compare_call->precedingCalls : $compare_call->subsequentCalls
                                        , static fn(ServiceCallWithDestination $compare_filter_call) => $compare_filter_call->isInSamePortion($compare_subsequent_call)
                                            && ($arrival_mode
                                                ? $compare_filter_call->timestamp < $compare_subsequent_call->timestamp
                                                : $compare_filter_call->timestamp > $compare_subsequent_call->timestamp
                                            )
                                    ) !== [];
                            }
                        ) !== []
                ) === []
            ) {
                $result[] = $call_location;
                if ($via) {
                    break;
                }
            }
        }
        return $result;
    }
}