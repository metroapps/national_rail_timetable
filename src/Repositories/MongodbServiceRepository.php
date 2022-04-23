<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Repositories;

use Miklcct\NationalRailJourneyPlanner\Enums\CallType;
use Miklcct\NationalRailJourneyPlanner\Models\Date;
use Miklcct\NationalRailJourneyPlanner\Models\ServiceEntry;
use MongoDB\Collection;
use function array_chunk;
use function array_filter;
use function array_map;
use function array_unique;
use function array_values;

class MongodbServiceRepository extends AbstractServiceRepository {
    public function __construct(
        private readonly Collection $servicesCollection
        , private readonly Collection $associationsCollection
    ) {}

    protected function getServiceEntries(array $uids, Date $date, bool $three_days = false) : array {
        $query_results = $this->servicesCollection->find(
            [
                'uid' => ['$in' => $uids],
                'period.from' => ['$lte' => $date->addDays(+$three_days)],
                'period.to' => ['$gte' => $date->addDays(-$three_days)],
            ]
        )->toArray();
        $results = [];
        foreach ($uids as $new_uid) {
            $results[$new_uid] = array_values(
                array_filter(
                    $query_results
                    , static fn(ServiceEntry $service) => $service->uid === $new_uid
                )
            );
        }
        return $results;
    }

    protected function getAssociationEntries(string $uid, Date $date) : array {
        return $this->associationsCollection->find(
            [
                '$or' => [['primaryUid' => $uid], ['secondaryUid' => $uid]],
                'period.from' => ['$lte' => $date->addDays(1)],
                'period.to' => ['$gte' => $date->addDays(-1)],
            ]
        )
            ->toArray();
    }

    public function insertServices(array $services) : void {
        foreach (array_chunk($services, 10000) as $chunk) {
            if ($chunk !== []) {
                $this->servicesCollection->insertMany($chunk);
            }
        }
    }

    public function insertAssociations(array $associations) : void {
        if ($associations !== []) {
            $this->associationsCollection->insertMany($associations);
        }
    }

    public function getUidsAtStation(string $crs, Date $date, CallType $call_type) : array {
        return array_values(
            array_unique(
                array_map(
                    static fn(array $item) => $item['uid']
                    , $this->servicesCollection->find(
                        [
                            'points' => [
                                '$elemMatch' => [
                                    'location.crsCode' => $crs,
                                    match($call_type) {
                                        CallType::DEPARTURE => 'workingDeparture',
                                        CallType::ARRIVAL => 'workingArrival',
                                        CallType::PASS => 'pass',
                                    } => ['$ne' => null]
                                ]
                            ],
                            'period.from' => ['$lte' => $date->addDays(1)],
                            'period.to' => ['$gte' => $date->addDays(-1)],
                        ]
                        , ['projection' => ['uid' => 1, '_id' => 0], 'typeMap' => ['root' => 'array']]
                    )->toArray()
                )
            )
        );
    }

    public function addIndexes() : void {
        $this->servicesCollection->createIndexes(
            [
                ['key' => ['uid' => 1]],
                ['key' => ['points.location.crsCode' => 1, 'period.from' => 1, 'period.to' => 1]],
            ]
        );
        $this->associationsCollection->createIndexes(
            [
                ['key' => ['primaryUid' => 1]],
                ['key' => ['secondaryUid' => 1]],
            ]
        );
    }
}