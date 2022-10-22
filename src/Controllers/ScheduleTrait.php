<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Controllers;

use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\FixedLink;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Miklcct\NationalRailTimetable\Models\Station;
use Miklcct\NationalRailTimetable\Repositories\FixedLinkRepositoryInterface;

trait ScheduleTrait {
    private readonly FixedLinkRepositoryInterface $fixedLinkRepository;
    private function getFixedLinks(BoardQuery $query) : array {
        $station = $query->station;
        if (!$station instanceof Station) {
            return [];
        }
        /** @var FixedLink[] $fixed_links */
        $fixed_links = [];
        $fixed_link_departure_time = $query->getFixedLinkDepartureTime();
        $arrival_mode = $query->arrivalMode;
        $destinations = $query->filter;
        $date = $query->date ?? Date::today();
        foreach ($this->fixedLinkRepository->get($station->crsCode, null) as $fixed_link) {
            if ($fixed_link_departure_time !== null) {
                $arrival_time = $fixed_link->getArrivalTime($fixed_link_departure_time, $arrival_mode);
                $existing = $fixed_links[$fixed_link->destination->crsCode] ?? null;
                if (
                    ($destinations === []
                        || in_array(
                            $fixed_link->destination->crsCode,
                            array_map(static fn(LocationWithCrs $destination) => $destination->getCrsCode(),
                                $destinations),
                            true
                        ))
                    && $arrival_time !== null
                    && (
                        !$existing
                        || ($arrival_mode ? $arrival_time > $existing->getArrivalTime($fixed_link_departure_time)
                            : $arrival_time < $existing->getArrivalTime($fixed_link_departure_time))
                        || $arrival_time == $existing->getArrivalTime($fixed_link_departure_time)
                        && $fixed_link->priority > $existing->priority
                    )
                ) {
                    $fixed_links[$fixed_link->destination->crsCode] = $fixed_link;
                }
            } elseif ($fixed_link->isActiveOnDate($date)) {
                $fixed_links[] = $fixed_link;
            }
        }

        usort(
            $fixed_links
            , static fn(FixedLink $a, FixedLink $b) => $a->origin->crsCode === $b->origin->crsCode
            ? $a->destination->crsCode === $b->destination->crsCode
                ? $a->startTime->toHalfMinutes() <=> $b->startTime->toHalfMinutes()
                : $a->destination->crsCode <=> $b->destination->crsCode
            : $a->origin->crsCode <=> $b->origin->crsCode
        );
        return $fixed_links;
    }
}