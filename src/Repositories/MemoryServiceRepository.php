<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Repositories;

use DateTimeImmutable;
use Miklcct\NationalRailJourneyPlanner\Enums\CallType;
use Miklcct\NationalRailJourneyPlanner\Enums\TimeType;
use Miklcct\NationalRailJourneyPlanner\Models\AssociationEntry;
use Miklcct\NationalRailJourneyPlanner\Models\Date;
use Miklcct\NationalRailJourneyPlanner\Models\DatedService;
use Miklcct\NationalRailJourneyPlanner\Models\ServiceEntry;
use function array_filter;
use function array_keys;
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

    public function getService(string $uid, Date $date, bool $permanent_only = false) : ?DatedService {
        $result = null;
        foreach ($this->services[$uid] ?? [] as $service) {
            if ($service->runsOnDate($date) && $service->isSuperior($result, $permanent_only)) {
                $result = $service;
            }
        }
        return $result === null ? null : new DatedService($result, $date);
    }

    public function getServicesAtStation(
        string $crs,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        CallType $call_type,
        TimeType $time_type = TimeType::PUBLIC,
        bool $permanent_only = false
    ) : array {
        $results = [];
        foreach (array_keys($this->services) as $uid) {
            $from_date = Date::fromDateTimeInterface($from)->addDays(-1);
            $to_date = Date::fromDateTimeInterface($to);
            for ($date = $from_date; $date->compare($to_date) <= 0; $date = $date->addDays(1)) {
                $dated_service = $this->getService($uid, $date);
                if ($dated_service !== null) {
                    $results[] = $dated_service->getCallsAt($crs, $call_type, $time_type, $from, $to);
                }
            }
        }
        return $this->sortCallResults(array_merge(...$results), $call_type, $time_type);
    }
}