<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateTimeImmutable;
use LogicException;
use Miklcct\NationalRailJourneyPlanner\Enums\TimeType;
use Miklcct\NationalRailJourneyPlanner\Models\Points\TimingPoint;
use MongoDB\BSON\Persistable;

class DatedService implements Persistable {
    use BsonSerializeTrait;

    public function __construct(
        public readonly ServiceEntry $service
        , public readonly Date $date
    ) {}

    /**
     * @param TimeType $time_type
     * @param string $crs
     * @return ServiceCall[]
     */
    public function getCalls(
        TimeType $time_type
        , ?string $crs = null
        , DateTimeImmutable $from = null
        , DateTimeImmutable $to = null
    ) : array {
        $service = $this->service;
        if (!$service instanceof Service) {
            throw new LogicException('The service within DatedService must be a proper Service to get calling points.');
        }
        return array_values(
            array_filter(
                array_map(
                    function (TimingPoint $point) use ($service, $crs, $time_type) : ?ServiceCall {
                        if ($crs !== null && $point->location->crsCode !== $crs) {
                            return null;
                        }
                        $time = $point->getTime($time_type);
                        $timestamp = $time === null ? null : $this->date->toDateTimeImmutable($time);
                        return $time === null ? null : new ServiceCall(
                            $timestamp
                            , $time_type
                            , $this->service->uid
                            , $this->date
                            , $point
                            , $service->getServicePropertyAtTime($time)
                        );
                    }
                    , $service->points
                )
                , static function (?ServiceCall $service_call) use ($to, $from) {
                    $timestamp = $service_call?->timestamp;
                    return $timestamp !== null
                        && ($from === null || $timestamp >= $from)
                        && ($to === null || $timestamp < $to);
                }
            )
        );
    }
}