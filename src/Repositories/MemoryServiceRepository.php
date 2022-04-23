<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Repositories;

use Miklcct\NationalRailJourneyPlanner\Enums\CallType;
use Miklcct\NationalRailJourneyPlanner\Models\AssociationEntry;
use Miklcct\NationalRailJourneyPlanner\Models\Date;
use Miklcct\NationalRailJourneyPlanner\Models\Points\TimingPoint;
use Miklcct\NationalRailJourneyPlanner\Models\Service;
use Miklcct\NationalRailJourneyPlanner\Models\ServiceEntry;
use function array_filter;
use function array_map;
use function array_values;

class MemoryServiceRepository extends AbstractServiceRepository {
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

    public function getUidsAtStation(string $crs, Date $date, CallType $call_type) : array {
        return array_values(
            array_unique(
                array_map(
                    static fn(ServiceEntry $service) => $service->uid
                    , array_filter(
                        array_merge(
                            ...array_values($this->services)
                        )
                        , static fn(ServiceEntry $service) =>
                            $service instanceof Service && array_filter(
                                $service->points
                                , static fn(TimingPoint $point) =>
                                    $point->location->crsCode === $crs
                                    && $service->period->from->compare($date->addDays(1)) <= 0
                                    && $service->period->to->compare($date->addDays(-1)) >= 0
                                    && match($call_type) {
                                        CallType::DEPARTURE => $point->workingDeparture ?? null,
                                        CallType::ARRIVAL => $point->workingArrival ?? null,
                                        CallType::PASS => $point->pass ?? null
                                    } !== null
                            ) !== []
                    )
                )
            )
        );
    }

    protected function getServiceEntries(array $uids, Date $date, bool $three_days = false) : array {
        return array_combine(
            $uids
            , array_map(
                fn($uid) => array_values(
                    array_filter(
                        $this->services[$uid] ?? [], fn(ServiceEntry $service) =>
                            $service->period->from->compare($date->addDays(+$three_days)) <= 0
                            && $service->period->to->compare($date->addDays(-$three_days)) >= 0
                    )
                )
                , $uids
            )
        );
    }

    protected function getAssociationEntries(string $uid, Date $date) : array {
        return array_values(
            array_filter(
                $this->associations[$uid] ?? []
                , fn(AssociationEntry $association) =>
                    $association->period->from->compare($date->addDays(1)) <= 0
                    && $association->period->to->compare($date->addDays(-1)) >= 0
            )
        );
    }

    /** @var array<string, ServiceEntry[]> */
    private array $services = [];

    /** @var array<string, AssociationEntry[]> */
    private array $associations = [];
}