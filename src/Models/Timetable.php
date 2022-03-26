<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateInterval;
use DateTimeImmutable;
use Miklcct\NationalRailJourneyPlanner\Enums\AssociationCategory;
use Miklcct\NationalRailJourneyPlanner\Enums\AssociationDay;
use Miklcct\NationalRailJourneyPlanner\Enums\AssociationType;
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
     * @return DatedService[]
     */
    public function getServices(
        DateTimeImmutable $from
        , DateTimeImmutable $to
        , bool $include_non_passenger = false
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
                    && ($include_non_passenger
                        || $service->serviceProperty->trainCategory->isPassengerTrain())
                ) {
                    $origin = $service->getOrigin();
                    $destination = $service->getDestination();
                    $departure_time = $origin->getPublicDeparture() ?? $origin->workingDeparture;
                    $arrival_time = $destination->getPublicArrival() ?? $destination->workingArrival;
                    if (
                        $arrival_time->getDateTimeOnDate($date) > $from
                        && $departure_time->getDateTimeOnDate($date) < $to
                    ) {
                        $result[] = new DatedService($service, $date);
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Get associations of the specified service
     *
     * If $from is specified, only the following associations happening after
     * it will be returned.
     * - joining another train
     * - dividing to form another train
     * - forming another service at the end
     *
     * If $to is specified, only the following associations happening before it
     * will be returned.
     * - another train joining
     * - dividing from another train
     * - formed from another service at the beginning
     *
     * @param DatedService $dated_service
     * @param Time|null $from
     * @param Time|null $to
     * @return DatedAssociation[]
     */
    public function getAssociations(
        DatedService $dated_service
        , ?Time $from = null
        , ?Time $to = null
        , bool $include_non_passenger = false
    ) : array {
        $service = $dated_service->service;
        $departure_date = $dated_service->date;
        $one_day = new DateInterval('P1D');
        $results = [];
        foreach ($this->associations[$service->uid] ?? [] as $association) {
            if (
                $association instanceof Association
                && ($include_non_passenger || $association->type === AssociationType::PASSENGER)
            ) {
                $primary_departure_date
                    = $association->secondaryUid === $service->uid
                        ? match ($association->day) {
                            AssociationDay::YESTERDAY => $departure_date->add($one_day),
                            AssociationDay::TODAY => $departure_date,
                            AssociationDay::TOMORROW => $departure_date->sub($one_day),
                        }
                        : $departure_date;
                if ($association->period->isActive($primary_departure_date)) {
                    $results[] = new DatedAssociation($association, $primary_departure_date);
                }
            }
        }

        $results = array_filter(
            $results
            , function (DatedAssociation $dated_association) use ($service) : bool {
                $association = $dated_association->associationEntry;
                return $dated_association->associationEntry->shortTermPlanning !== ShortTermPlanning::PERMANENT
                    || [] === array_filter(
                        $this->associations[$service->uid] ?? []
                        , static fn(AssociationEntry $other) : bool =>
                            $association->primaryUid === $other->primaryUid
                            && $association->secondaryUid === $other->secondaryUid
                            && $association->location === $other->location
                            && $association->primarySuffix === $other->primarySuffix
                            && $association->secondarySuffix === $other->secondarySuffix
                            && $other->shortTermPlanning !== ShortTermPlanning::PERMANENT
                            && $other->period->isActive($dated_association->date)
                );
            }
        );

        if ($from !== null) {
            $results = $service instanceof Service
                ? array_filter(
                    $results
                    , static fn(DatedAssociation $association) =>
                        $association->associationEntry instanceof Association
                        && $service->getAssociationTime($association->associationEntry)->toHalfMinutes()
                            > $from->toHalfMinutes()
                        && match ($association->associationEntry->category) {
                            AssociationCategory::DIVIDE, AssociationCategory::NEXT =>
                                $service->uid === $association->associationEntry->primaryUid,
                            AssociationCategory::JOIN => $service->uid === $association->associationEntry->secondaryUid,
                        }
                )
                : [];
        }
        if ($to !== null) {
            $results = $service instanceof Service
                ? array_filter(
                    $results
                    , static fn(DatedAssociation $association) =>
                        $association->associationEntry instanceof Association
                        && $service->getAssociationTime($association->associationEntry)->toHalfMinutes()
                            < $to->toHalfMinutes()
                    && match ($association->associationEntry->category) {
                        AssociationCategory::DIVIDE, AssociationCategory::NEXT =>
                            $service->uid === $association->associationEntry->secondaryUid,
                        AssociationCategory::JOIN => $service->uid === $association->associationEntry->primaryUid,
                    }
                )
                : [];
        }

        return $results;
    }

    /**
     * Get the "real" destination of the train, taking joins and splits into account.
     *
     * @param DatedService $dated_service
     * @param ?Time $time
     * @return DestinationPoint[]
     */
    public function getRealDestinations(
        DatedService $dated_service
        , ?Time $time = null
    ) : array {
        $service = $dated_service->service;
        if (!$service instanceof Service) {
            return [];
        }
        $associations = $this->getAssociations(
            $dated_service
            , $time ?? $service->getOrigin()->workingDeparture
        );
        $join = array_filter(
            $associations
            , static fn(DatedAssociation $association) =>
                $association->associationEntry instanceof Association
                && $association->associationEntry->category === AssociationCategory::JOIN
        )[0] ?? null;
        $divides = array_filter(
            $associations
            , static fn(DatedAssociation $association) =>
                $association->associationEntry instanceof Association
                && $association->associationEntry->category === AssociationCategory::DIVIDE
        );
        return array_merge(
            $join === null
                ? [$service->getDestination()->location]
                : $this->getRealDestinations(
                    new DatedService(
                        $this->getUidOnDate($join->associationEntry->primaryUid, $join->date)
                        , $join->date
                    )
                )
            , ...array_map(
                function (DatedAssociation $divide) {
                    assert($divide->associationEntry instanceof Association);
                    $one_day = new DateInterval('P1D');
                    $secondary_date = match ($divide->associationEntry->day) {
                        AssociationDay::YESTERDAY => $divide->date->sub($one_day),
                        AssociationDay::TODAY => $divide->date,
                        AssociationDay::TOMORROW => $divide->date->add($one_day),
                    };
                    return $this->getRealDestinations(
                        new DatedService(
                            $this->getUidOnDate($divide->associationEntry->secondaryUid, $secondary_date)
                            , $secondary_date
                        )
                    );
                }
                , $divides
            )
        );
    }

    public Stations $stations;

    /** @var array<string, ServiceEntry[]> */
    private array $services = [];

    /** @var array<string, AssociationEntry[]> */
    private array $associations = [];
}