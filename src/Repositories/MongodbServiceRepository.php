<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Repositories;

use MongoDB\Collection;
use function array_chunk;
use function array_unique;

class MongodbServiceRepository extends AbstractServiceRepository {
    public function __construct(
        private readonly Collection $servicesCollection
        , private readonly Collection $associationsCollection
    ) {}

    protected function getServicesByUid(string $uid) : iterable {
        return $this->serviceCache[$uid] ??= $this->servicesCollection->find(['uid' => $uid]);
    }

    protected function listAllUids() : iterable {
        return array_values(
            array_unique(
                array_map(
                    static fn(array $item) => $item['uid']
                    , $this->servicesCollection->find(
                        []
                        , ['projection' => ['uid' => 1, '_id' => 0], 'typeMap' => ['root' => 'array']]
                    )
                        ->toArray()
                )
            )
        );
    }

    protected function getAssociationsByUid(string $uid) : array {
        return $this->associationCache[$uid]
            ??= $this->associationsCollection->find(['$or' => [['primaryUid' => $uid], ['secondaryUid' => $uid]]])
                ->toArray();
    }

    public function insertServices(array $services) : void {
        foreach (array_chunk($services, 10000) as $chunk) {
            if ($chunk !== []) {
                $this->servicesCollection->insertMany($chunk);
                $this->clearCache();
            }
        }
    }

    public function insertAssociations(array $associations) : void {
        if ($associations !== []) {
            $this->associationsCollection->insertMany($associations);
            $this->clearCache();
        }
    }

    public function addIndexes() : void {
        $this->servicesCollection->createIndexes(
            [
                ['key' => ['uid' => 1]],
            ]
        );
        $this->associationsCollection->createIndexes(
            [
                ['key' => ['primaryUid' => 1]],
                ['key' => ['secondaryUid' => 1]],
            ]
        );
    }

    private function clearCache() : void {
    }

    private array $serviceCache = [];
    private array $associationCache = [];
}