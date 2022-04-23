<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Repositories;

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
     * @return array<string, ServiceEntry[]>
     */
    abstract protected function getServiceEntries(array $uids, Date $from, Date $to) : array;

    /**
     * @return AssociationEntry[]
     */
    abstract protected function getAssociationEntries(string $uid, Date $date) : array;

    public function getServicesByUids(
        array $uids
        , Date $from
        , Date $to = null
        , bool $permanent_only = false
    ) : array {
        $to ??= $from;
        $services = $this->getServiceEntries($uids, $from, $to);
        $results = [];
        foreach ($uids as $uid) {
            $results[$uid] = [];
            for ($actual_date = $from; $actual_date->compare($to) <= 0; $actual_date = $actual_date->addDays(1)) {
                $result = null;
                foreach ($services[$uid] as $service) {
                    if ($service->isSuperior($result, $permanent_only) && $service->runsOnDate($actual_date)) {
                        $result = $service;
                    }
                }
                if ($result !== null) {
                    $results[$uid][] = new DatedService($result, $actual_date);
                }
            }
        }
        return $results;
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

    public function getAssociations(
        DatedService $dated_service
        , ?Time $from = null
        , ?Time $to = null
        , bool $include_non_passenger = false
        , bool $permanent_only = false
    ) : array {
        $service = $dated_service->service;
        $departure_date = $dated_service->date;
        $results = [];
        $uid = $service->uid;
        $association_entries = $this->getAssociationEntries($uid, $departure_date);

        // process overlay
        $overlaid_associations = [-1 => [], 0 => [], 1 => []];
        foreach ($overlaid_associations as $date_offset => &$associations) {
            foreach ($association_entries as $association) {
                $association_date = $departure_date->addDays($date_offset);
                if ($association->period->isActive($association_date)) {
                    $found = false;
                    foreach ($associations as &$existing) {
                        if ($association->isSame($existing)) {
                            if ($association->isSuperior($existing, $permanent_only)) {
                                $existing = $association;
                            }
                            $found = true;
                        }
                    }
                    unset($existing);
                    if (!$found && (!$permanent_only || $association->shortTermPlanning === ShortTermPlanning::PERMANENT)) {
                        $associations[] = $association;
                    }
                }
            }
        }
        unset($associations);

        foreach ($overlaid_associations as $date_offset => $associations) {
            foreach ($associations as $association) {
                if (
                    $association instanceof Association
                    && ($include_non_passenger || $association->type === AssociationType::PASSENGER)
                ) {
                    $correct_date = $date_offset === (
                        $association->secondaryUid === $uid
                            ? match ($association->day) {
                                AssociationDay::YESTERDAY => 1,
                                AssociationDay::TODAY => 0,
                                AssociationDay::TOMORROW => -1,
                            }
                            : 0
                    );
                    if ($correct_date) {
                        $primary_departure_date = $departure_date->addDays($date_offset);
                        if ($association->secondaryUid === $uid) {
                            $primary_service = $this->getServicesByUids(
                                [$association->primaryUid]
                                , $primary_departure_date
                            )[$association->primaryUid][0];
                            $secondary_service = $dated_service;
                        } else {
                            $primary_service = $dated_service;
                            $secondary_departure_date = match ($association->day) {
                                AssociationDay::YESTERDAY => $departure_date->addDays(-1),
                                AssociationDay::TODAY => $departure_date,
                                AssociationDay::TOMORROW => $departure_date->addDays(1),
                            };
                            $secondary_service = $this->getServicesByUids(
                                [$association->secondaryUid]
                                , $secondary_departure_date
                            )[$association->secondaryUid][0];
                        }
                        $results[] = new DatedAssociation(
                            $association
                            , $primary_service
                            , $secondary_service
                        );
                    }
                }
            }
        }

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