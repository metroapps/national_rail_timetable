<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Miklcct\NationalRailJourneyPlanner\Enums\AssociationCategory;
use Miklcct\NationalRailJourneyPlanner\Enums\AssociationDay;
use Miklcct\NationalRailJourneyPlanner\Enums\ShortTermPlanning;
use function array_filter;
use function array_keys;
use function array_merge;

class Timetable {
    public function insertService(ServiceEntry $entry) : void {
        $this->services[$entry->uid][] = $entry;
    }

    public function insertAssociation(AssociationEntry $entry) : void {
        $this->associations[$entry->primaryUid][] = $entry;
        $this->associations[$entry->secondaryUid][] = $entry;
    }

    public function getUidOnDate(
        string $uid
        , DateTimeImmutable $date
        , bool $include_short_term_planning = true
    ) : ?ServiceEntry {
        $result = null;
        foreach ($this->services[$uid] ?? [] as $service) {
            if (
                ($include_short_term_planning
                    || $service->shortTermPlanning === ShortTermPlanning::PERMANENT)
                && ($result === null
                    || $service->shortTermPlanning !== ShortTermPlanning::PERMANENT)
                && $service->runsOnDate($date)
            ) {
                $result = $service;
            }
        }
        return $result;
    }

    /**
     * Get all public services which are active in the period specified
     *
     * @return array<string, Service[]> where the key is the departure date
     * in YYYY-MM-DD format
     */
    public function getServices(
        DateTimeImmutable $from
        , DateTimeImmutable $to
    ) : array {
        static $one_day = null;
        if ($one_day === null) {
            $one_day = new DateInterval('P1D');
        }
        $result = [];
        for (
            $date = $from->sub($one_day);
            $date < $to;
            $date = $date->add($one_day)
        ) {
            foreach (array_keys($this->services) as $uid) {
                $service = $this->getUidOnDate($uid, $date);
                if (
                    $service instanceof Service
                    && $service->serviceProperty->trainCategory
                        ->isPassengerTrain()
                ) {
                    $origin = $service->points[0];
                    $destination
                        = $service->points[count($service->points) - 1];
                    $departure_time = $origin->getPublicDeparture();
                    $arrival_time = $destination->getPublicArrival();
                    // We assume that the origin and destination of a passenger
                    // train is always for passenger use.
                    assert($departure_time instanceof Time);
                    assert($arrival_time instanceof Time);
                    if (
                        $arrival_time->getDateTimeOnDate($date) > $from
                        && $departure_time->getDateTimeOnDate($date) < $to
                    ) {
                        $result[$date->format('Y-m-d')][] = $service;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Get associations of the specified service
     *
     * If $from or $to is specified, only associations happening between them
     * are returned.
     *
     * @param string $uid
     * @param DateTimeImmutable $departure_date
     * @param Time|null $from
     * @param Time|null $to
     * @return Association[]
     */
    public function getAssociations(
        Service $service
        , DateTimeImmutable $departure_date
        , ?Time $from = null
        , ?Time $to = null
    ) : array {
        $one_day = new DateInterval('P1D');
        $results = [];
        foreach ($this->associations[$service->uid] ?? [] as $association) {
            if ($association instanceof Association) {
                $primary_departure_date
                    = $association->secondaryUid === $service->uid
                        ? match ($association->day) {
                            AssociationDay::YESTERDAY => $departure_date->add($one_day),
                            AssociationDay::TODAY => $departure_date,
                            AssociationDay::TOMORROW => $departure_date->sub($one_day),
                        }
                        : $departure_date;
                if ($association->period->isActive($primary_departure_date)) {
                    $results[$primary_departure_date->format('Y-m-d')][] = $association;
                }
            }
        }

        foreach ($this->associations[$service->uid] ?? [] as $stp_association) {
            if ($stp_association->shortTermPlanning !== ShortTermPlanning::PERMANENT) {
                foreach ($results as $date_string => &$associations) {
                    $date = new \Safe\DateTimeImmutable($date_string, new DateTimeZone('Europe/London'));
                    if ($stp_association->period->isActive($date)) {
                        $associations = array_values(
                            array_filter(
                                $associations
                                , static fn(Association $association)
                                    => !($association->primaryUid === $stp_association->primaryUid
                                        && $association->secondaryUid === $stp_association->secondaryUid
                                        && $association->location === $stp_association->location
                                        && $association->primarySuffix === $stp_association->primarySuffix
                                        && $association->secondarySuffix === $stp_association->secondarySuffix
                                        && $association->shortTermPlanning === ShortTermPlanning::PERMANENT)
                            )
                        );
                    }
                }
                unset($associations);
            }
        }

        $results = array_merge(...array_values($results));
        if ($from !== null) {
            $results = array_filter(
                $results
                , static fn(Association $association) =>
                    $service->getAssociationTime($association)->toHalfMinutes() > $from->toHalfMinutes()
            );
        }
        if ($to !== null) {
            $results = array_filter(
                $results
                , static fn(Association $association) =>
                    $service->getAssociationTime($association)->toHalfMinutes() < $to->toHalfMinutes()
            );
        }

        return $results;
    }

    /**
     * Get the "real" destination of the train, taking joins and splits into account.
     *
     * @param Service $service
     * @param DateTimeImmutable $departure_date
     * @param ?Time $time
     * @return DestinationPoint[]
     */
    public function getRealDestinations(
        Service $service
        , DateTimeImmutable $departure_date
        , ?Time $time = null
    ) : array {
        $associations = $this->getAssociations($service, $departure_date, $time);
        $joining_train = null;
        $joining_date = null;
        $one_day = new DateInterval('P1D');
        $dividing_trains = [];
        foreach ($associations as $association) {
            if (
                $association->category === AssociationCategory::DIVIDE
                && $association->primaryUid === $service->uid
            ) {
                $dividing_date = match ($association->day) {
                    AssociationDay::YESTERDAY => $departure_date->sub($one_day),
                    AssociationDay::TODAY => $departure_date,
                    AssociationDay::TOMORROW => $departure_date->add($one_day),
                };
                $dividing_trains[] = [
                    $this->getUidOnDate(
                        $association->secondaryUid
                        , $dividing_date
                    )
                    , $dividing_date
                ];
            }

            if (
                $association->category === AssociationCategory::JOIN
                && $association->secondaryUid === $service->uid
            ) {
                $joining_date = match ($association->day) {
                    AssociationDay::YESTERDAY => $departure_date->add($one_day),
                    AssociationDay::TODAY => $departure_date,
                    AssociationDay::TOMORROW => $departure_date->sub($one_day),
                };
                $joining_train = $this->getUidOnDate(
                    $association->primaryUid
                    , $joining_date
                );
                if (!$joining_train instanceof Service) {
                    $joining_train = null;
                }
            }
        }
        return array_merge(
            $joining_train === null
                ? [$service->points[count($service->points) - 1]->location]
                : $this->getRealDestinations($joining_train, $joining_date)
            , ...array_map(
                fn(array $arg) => $this->getRealDestinations($arg[0], $arg[1])
                , $dividing_trains
            )
        );
    }

    public Stations $stations;

    /** @var array<string, ServiceEntry[]> */
    private array $services = [];

    /** @var array<string, AssociationEntry[]> */
    private array $associations = [];
}