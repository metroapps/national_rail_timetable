<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Repositories;

use DateTimeImmutable;
use Miklcct\NationalRailJourneyPlanner\Enums\AssociationCategory;
use Miklcct\NationalRailJourneyPlanner\Enums\AssociationDay;
use Miklcct\NationalRailJourneyPlanner\Enums\AssociationType;
use Miklcct\NationalRailJourneyPlanner\Enums\ShortTermPlanning;
use Miklcct\NationalRailJourneyPlanner\Models\Association;
use Miklcct\NationalRailJourneyPlanner\Models\AssociationEntry;
use Miklcct\NationalRailJourneyPlanner\Models\Date;
use Miklcct\NationalRailJourneyPlanner\Models\DatedAssociation;
use Miklcct\NationalRailJourneyPlanner\Models\DatedService;
use Miklcct\NationalRailJourneyPlanner\Models\FullService;
use Miklcct\NationalRailJourneyPlanner\Models\Service;
use Miklcct\NationalRailJourneyPlanner\Models\ServiceEntry;
use Miklcct\NationalRailJourneyPlanner\Models\Time;

abstract class AbstractServiceRepository implements ServiceRepositoryInterface {
    /**
     * @return ServiceEntry[]
     */
    abstract protected function getServicesByUid(string $uid) : iterable;

    /**
     * @return string[]
     */
    abstract protected function listAllUids() : iterable;

    /**
     * @return AssociationEntry[]
     */
    abstract protected function getAssociationsByUid(string $uid) : array;

    public function getUidOnDate(
        string $uid
        , Date $date
        , bool $permanent_only = false
    ) : ?ServiceEntry {
        $result = null;
        foreach ($this->getServicesByUid($uid) as $service) {
            if ($service->isSuperior($result, $permanent_only) && $service->runsOnDate($date)) {
                $result = $service;
            }
        }
        return $result;
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
                                && $service->date->toDateTimeImmutable()
                                    == $previous->date->toDateTimeImmutable()
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

    public function getServices(
        DateTimeImmutable $from
        , DateTimeImmutable $to
        , bool $include_non_passenger = false
    ) : array {
        $result = [];
        for (
            $date = Date::fromDateTimeInterface($from)->addDays(-1);
            $date->toDateTimeImmutable() < $to;
            $date = $date->addDays(1)
        ) {
            foreach ($this->listAllUids() as $uid) {
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
                        $date->toDateTimeImmutable($arrival_time) > $from
                        && $date->toDateTimeImmutable($departure_time) < $to
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
        $results = [];
        $uid = $service->uid;
        $associations = $this->getAssociationsByUid($uid);
        foreach ($associations as $association) {
            if (
                $association instanceof Association
                && ($include_non_passenger || $association->type === AssociationType::PASSENGER)
            ) {
                $primary_departure_date
                    = $association->secondaryUid === $uid
                    ? match ($association->day) {
                        AssociationDay::YESTERDAY => $departure_date->addDays(1),
                        AssociationDay::TODAY => $departure_date,
                        AssociationDay::TOMORROW => $departure_date->addDays(-1),
                    }
                    : $departure_date;
                if ($association->period->isActive($primary_departure_date)) {
                    if ($association->secondaryUid === $uid) {
                        $primary_service = $this->getUidOnDate($association->primaryUid, $primary_departure_date);
                        $secondary_departure_date = $departure_date;
                        $secondary_service = $dated_service->service;
                    } else {
                        $primary_service = $dated_service->service;
                        $secondary_departure_date = match ($association->day) {
                            AssociationDay::YESTERDAY => $departure_date->addDays(-1),
                            AssociationDay::TODAY => $departure_date,
                            AssociationDay::TOMORROW => $departure_date->addDays(1),
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
            , function (DatedAssociation $dated_association) use ($associations) : bool {
            $association = $dated_association->associationEntry;
            return $dated_association->associationEntry->shortTermPlanning !== ShortTermPlanning::PERMANENT
                || [] === array_filter(
                    $associations
                    , static fn(AssociationEntry $other) : bool => $association->primaryUid === $other->primaryUid
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
                    , static fn(DatedAssociation $association) => $association->associationEntry instanceof Association
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
                    , static fn(DatedAssociation $association) => $association->associationEntry instanceof Association
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
}