<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Miklcct\NationalRailJourneyPlanner\Attributes\ElementType;
use MongoDB\BSON\Persistable;

class FixedLink implements Persistable {
    use BsonSerializeTrait;

    public function __construct(
        public readonly string $mode
        , public readonly Station $origin
        , public readonly Station $destination
        , public readonly int $transferTime
        , public readonly Time $startTime
        , public readonly Time $endTime
        , public readonly int $priority
        , public readonly ?Date $startDate
        , public readonly ?Date $endDate
        , ?array $weekdays
    ) {
        $this->weekdays = $weekdays;
    }

    public function getArrivalTime(DateTimeImmutable $departure) : ?DateTimeImmutable {
        $timezone = new DateTimeZone('Europe/London');
        $departure = $departure->setTimezone($timezone);
        $time = Time::fromDateTimeInterface($departure);
        $date_valid = $this->weekdays[(int)$departure->format('w')]
            && ($this->startDate !== null ? $this->startDate->toDateTimeImmutable(new Time(0, 0), $timezone) <= $departure : true)
            && ($this->endDate !== null ? $this->endDate->toDateTimeImmutable(new Time(23, 59, true), $timezone) >= $departure : true);
        $time_valid = $this->startTime->toHalfMinutes() <= $time->toHalfMinutes()
            && $this->endTime->toHalfMinutes() >= $time->toHalfMinutes();
        if ($date_valid && $time_valid) return $departure->add(new DateInterval(sprintf('PT%dM', $this->transferTime)));
        if ($date_valid && $time->toHalfMinutes() < $this->startTime->toHalfMinutes()) {
            return $this->getArrivalTime($departure->setTime($this->startTime->hours, $this->startTime->minutes));
        }
        if ($this->endDate !== null && $departure > $this->endDate->toDateTimeImmutable(new Time(23, 59, true))) {
            return null;
        }
        return $this->getArrivalTime($departure->add(new DateInterval('P1D'))->setTime(0, 0));
    }

    /** @var bool[] 7 bits specifying if it is active on each of the weekdays */
    #[ElementType('bool')]
    public readonly array $weekdays;
}