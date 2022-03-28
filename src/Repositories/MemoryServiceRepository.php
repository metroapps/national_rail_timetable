<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Repositories;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Miklcct\NationalRailJourneyPlanner\Enums\AssociationCategory;
use Miklcct\NationalRailJourneyPlanner\Enums\AssociationDay;
use Miklcct\NationalRailJourneyPlanner\Enums\AssociationType;
use Miklcct\NationalRailJourneyPlanner\Enums\ShortTermPlanning;
use Miklcct\NationalRailJourneyPlanner\Models\Association;
use Miklcct\NationalRailJourneyPlanner\Models\AssociationEntry;
use Miklcct\NationalRailJourneyPlanner\Models\DatedAssociation;
use Miklcct\NationalRailJourneyPlanner\Models\DatedService;
use Miklcct\NationalRailJourneyPlanner\Models\FullService;
use Miklcct\NationalRailJourneyPlanner\Models\Service;
use Miklcct\NationalRailJourneyPlanner\Models\ServiceEntry;
use Miklcct\NationalRailJourneyPlanner\Models\Time;
use function array_filter;
use function array_keys;
use function array_merge;
use function array_slice;

class MemoryServiceRepository implements ServiceRepositoryInterface {
    public function insertServices(array $services) : void {
        foreach ($services as $service) {
            $this->services[$service->uid][] = $service;
        }
    }

    public function insertAssociations(array $associations) : void {
        foreach ($associations as $association) {
            $this->associations[$association->primaryUid][] = $association;
            $this->associations[$association->secondaryUid][] = $association;
        }
    }

    public function getUidOnDate(
        string $uid
        , DateTimeImmutable $date
        , bool $permanent_only = false
    ) : ?ServiceEntry {
        $result = null;
        foreach ($this->services[$uid] ?? [] as $service) {
            if (
                (!$permanent_only
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
                    $departure_time = $origin->getPublicOrWorkingDeparture();
                    $arrival_time = $destination->getPublicOrWorkingArrival();
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
                    if ($association->secondaryUid === $service->uid) {
                        $primary_service = $this->getUidOnDate($association->primaryUid, $primary_departure_date);
                        $secondary_departure_date = $departure_date;
                        $secondary_service = $dated_service->service;
                    } else {
                        $primary_service = $dated_service->service;
                        $secondary_departure_date = match ($association->day) {
                            AssociationDay::YESTERDAY => $departure_date->sub($one_day),
                            AssociationDay::TODAY => $departure_date,
                            AssociationDay::TOMORROW => $departure_date->add($one_day),
                        };
                        $secondary_service = $this->getUidOnDate($association->secondaryUid, $secondary_departure_date);
                    }
                    $results[] = new DatedAssociation(
                        $association
                        , new DatedService($primary_service, $primary_departure_date)
                        , new DatedService($secondary_service, $secondary_departure_date)
                    );
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
                            && $other->period->isActive($dated_association->primaryService->date)
                );
            }
        );

        $from_results = null;
        $to_results = null;

        if ($from !== null) {
            $from_results = $service instanceof Service
                ? array_filter(
                    $results
                    , static fn(DatedAssociation $association) =>
                        $association->associationEntry instanceof Association
                        && $service->getAssociationTime($association->associationEntry)->toHalfMinutes()
                            > $from->toHalfMinutes()
                        && match ($association->associationEntry->category) {
                            AssociationCategory::DIVIDE, AssociationCategory::NEXT =>
                                $service->uid === $association->associationEntry->primaryUid,
                            AssociationCategory::JOIN =>
                                $service->uid === $association->associationEntry->secondaryUid,
                        }
                )
                : [];
        }
        if ($to !== null) {
            $to_results = $service instanceof Service
                ? array_filter(
                    $results
                    , static fn(DatedAssociation $association) =>
                        $association->associationEntry instanceof Association
                        && $service->getAssociationTime($association->associationEntry)->toHalfMinutes()
                            < $to->toHalfMinutes()
                    && match ($association->associationEntry->category) {
                        AssociationCategory::DIVIDE, AssociationCategory::NEXT =>
                            $service->uid === $association->associationEntry->secondaryUid,
                        AssociationCategory::JOIN =>
                            $service->uid === $association->associationEntry->primaryUid,
                    }
                )
                : [];
        }

        return $from_results !== null
            ? array_merge($from_results, $to_results ?? [])
            : $to_results ?? $results;
    }

    public function getFullService(
        DatedService $dated_service
        , ?Time $boarding = null
        , ?Time $alighting = null
        , bool $include_non_passenger = false
        , array $recursed_services = []
    ) : FullService {
        $dated_associations = $this->getAssociations(
            $dated_service
            , $alighting ?? $boarding
            , $boarding ?? $alighting
            , $include_non_passenger
        );
        $divide_from = array_filter(
            $dated_associations
            , static fn(DatedAssociation $dated_association) =>
                $dated_service->service->uid === $dated_association->associationEntry->secondaryUid
                && $dated_association->associationEntry instanceof Association
                && $dated_association->associationEntry->category === AssociationCategory::DIVIDE
        )[0] ?? null;
        $join_to = array_filter(
            $dated_associations
            , static fn(DatedAssociation $dated_association) =>
                $dated_service->service->uid === $dated_association->associationEntry->secondaryUid
                && $dated_association->associationEntry instanceof Association
                && $dated_association->associationEntry->category === AssociationCategory::JOIN
        )[0] ?? null;
        $divides_and_joins = array_filter(
            $dated_associations
            , static fn(DatedAssociation $dated_association) =>
                $dated_service->service->uid === $dated_association->associationEntry->primaryUid
                && $dated_association->associationEntry instanceof Association
                && $dated_association->associationEntry->category !== AssociationCategory::NEXT
        );

        /** @var array<DatedAssociation|null> $dated_associations */
        $dated_associations = [$divide_from, ...$divides_and_joins, $join_to];

        $recursed_services[] = $dated_service;
        $timezone = new DateTimeZone('Europe/London');
        foreach ($dated_associations as &$dated_association) {
            if ($dated_association !== null) {
                /** @var DatedService[] $services */
                $services = [$dated_association->primaryService, $dated_association->secondaryService];
                foreach ($services as &$service) {
                    $recursed = array_values(
                        array_filter(
                            $recursed_services
                            , static fn(DatedService $previous) =>
                                $service->service->uid === $previous->service->uid
                                && $service->date->setTimezone($timezone)->setTime(0, 0)
                                    == $previous->date->setTimezone($timezone)->setTime(0, 0)
                        )
                    )[0] ?? null;
                    $service = $recursed ?? $this->getFullService(
                        $service
                        , $boarding
                        , $alighting
                        , $include_non_passenger
                        , $recursed_services
                    );
                }
                unset($service);
                $dated_association = new DatedAssociation(
                    $dated_association->associationEntry
                    , $services[0]
                    , $services[1]
                );
            }
        }
        unset($dated_association);

        return new FullService(
            $dated_service->service
            , $dated_service->date
            , $dated_associations[0]
            , array_slice($dated_associations, 1, count($dated_associations) - 2)
            , $dated_associations[count($dated_associations) - 1]
        );
    }

    /** @var array<string, ServiceEntry[]> */
    private array $services = [];

    /** @var array<string, AssociationEntry[]> */
    private array $associations = [];
}