<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateTimeImmutable;
use Miklcct\NationalRailJourneyPlanner\Enums\CallType;
use Miklcct\NationalRailJourneyPlanner\Enums\TimeType;
use Miklcct\NationalRailJourneyPlanner\Models\Points\TimingPoint;

class DatedService {
    public function __construct(
        public readonly ServiceEntry $service
        , public readonly Date $date
    ) {}

    /**
     * @param string $crs
     * @param CallType $call_type
     * @param TimeType $time_type
     * @return ServiceCall[]
     */
    public function getCallsAt(
        string $crs
        , CallType $call_type
        , TimeType $time_type
        , DateTimeImmutable $from = null
        , DateTimeImmutable $to = null
    ) : array {
        if (!$this->service instanceof Service) {
            return [];
        }
        return array_values(
            array_map(
                fn(TimingPoint $point) => new ServiceCall($this, $point)
                , array_filter(
                    $this->service->points
                    , function (TimingPoint $point) use ($to, $from, $crs, $call_type, $time_type) {
                        if ($point->location->crsCode !== $crs) {
                            return false;
                        }
                        $time = $point->getTime($call_type, $time_type);
                        return $time !== null
                            && ($from === null || $this->date->toDateTimeImmutable($time) >= $from)
                            && ($to === null || $this->date->toDateTimeImmutable($time) <= $to);
                    }
                )
            )
        );
    }
}