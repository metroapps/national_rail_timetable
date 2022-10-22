<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Views;

use DateInterval;
use DateTimeImmutable;
use Miklcct\NationalRailTimetable\Controllers\BoardQuery;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\FixedLink;

trait ScheduleTrait {
    protected readonly BoardQuery $query;

    protected function getFixedLinkUrl(FixedLink $fixed_link, ?DateTimeImmutable $departure_time) : string {
        return (
        new BoardQuery(
            $this->query->arrivalMode
            , $this->query->arrivalMode ? $fixed_link->origin : $fixed_link->destination
            , []
            , $this->query->connectingTime !== null
            ? Date::fromDateTimeInterface(
                $this->query->connectingTime->sub(new DateInterval($this->query->arrivalMode ? 'PT4H30M' : 'P0D'))
            )
            : $this->query->date ?? Date::today()
            , $departure_time === null
            ? null
            : $fixed_link->getArrivalTime($departure_time, $this->query->arrivalMode)
            , null
            , $this->query->permanentOnly
        )
        )->getUrl(self::URL);
    }
}