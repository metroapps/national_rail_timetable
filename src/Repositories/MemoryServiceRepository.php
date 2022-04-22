<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Repositories;

use Miklcct\NationalRailJourneyPlanner\Models\AssociationEntry;
use Miklcct\NationalRailJourneyPlanner\Models\ServiceEntry;
use function array_keys;

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

    protected function getServicesByUid(string $uid) : array {
        return $this->services[$uid] ?? [];
    }

    protected function listAllUids() : array {
        return array_keys($this->services);
    }

    protected function getAssociationsByUid(string $uid) : array {
        return $this->associations[$uid] ?? [];
    }

    /** @var array<string, ServiceEntry[]> */
    private array $services = [];

    /** @var array<string, AssociationEntry[]> */
    private array $associations = [];
}