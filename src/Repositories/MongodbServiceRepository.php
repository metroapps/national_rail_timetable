<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Repositories;

use DateTimeImmutable;
use Miklcct\NationalRailJourneyPlanner\Enums\BankHoliday;
use Miklcct\NationalRailJourneyPlanner\Enums\CallType;
use Miklcct\NationalRailJourneyPlanner\Enums\ShortTermPlanning;
use Miklcct\NationalRailJourneyPlanner\Enums\TimeType;
use Miklcct\NationalRailJourneyPlanner\Models\Date;
use Miklcct\NationalRailJourneyPlanner\Models\DatedService;
use Miklcct\NationalRailJourneyPlanner\Models\Service;
use Miklcct\NationalRailJourneyPlanner\Models\ServiceCall;
use Miklcct\NationalRailJourneyPlanner\Models\ServiceCancellation;
use Miklcct\NationalRailJourneyPlanner\Models\ServiceEntry;
use MongoDB\Collection;
use function array_chunk;
use function array_filter;
use function array_map;
use function array_values;

class MongodbServiceRepository extends AbstractServiceRepository {
    public function __construct(
        private readonly Collection $servicesCollection
        , private readonly Collection $associationsCollection
    ) {}

    protected function getServiceEntries(array $uids, Date $from, Date $to) : array {
        $query_results = $this->servicesCollection->find(
            [
                'uid' => ['$in' => $uids],
                'period.from' => ['$lte' => $to],
                'period.to' => ['$gte' => $from],
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

    public function getService(string $uid, Date $date, bool $permanent_only = false) : ?DatedService {
        $query_results = $this->servicesCollection->find(
            [
                'uid' => $uid,
                'period.from' => ['$lte' => $date],
                'period.to' => ['$gte' => $date],
            ] + ($permanent_only ? ['shortTermPlanning.value' => ShortTermPlanning::PERMANENT] : [])
            // this will order STP before permanent
            , ['sort' => ['shortTermPlanning.value' => 1]]
        );
        /** @var ServiceEntry $result */
        foreach ($query_results as $result) {
            if ($result->runsOnDate($date)) {
                return new DatedService($result, $date);
            }
        }
        return null;
    }

    public function getServicesAtStation(
        string $crs,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        CallType $call_type,
        TimeType $time_type = TimeType::PUBLIC,
        bool $permanent_only = false
    ) : array {
        $from_date = Date::fromDateTimeInterface($from)->addDays(-1);
        $to_date = Date::fromDateTimeInterface($to);
        // get skeleton services - incomplete objects!
        $query_results = $this->servicesCollection->find(
            [
                'points' => [
                    '$elemMatch' => [
                        'location.crsCode' => $crs,
                        match($call_type) {
                            CallType::DEPARTURE => 'workingDeparture',
                            CallType::ARRIVAL => 'workingArrival',
                            CallType::PASS => 'pass',
                        } => ['$ne' => null],
                    ],
                ],
                'period.from' => ['$lte' => $to_date],
                'period.to' => ['$gte' => $from_date],
            ] + ($permanent_only ? ['shortTermPlanning.value' => ShortTermPlanning::PERMANENT] : [])
            , ['projection' => ['uid' => 1, '_id' => 0, 'period' => 1, 'excludeBankHoliday' => 1, 'shortTermPlanning' => 1]]
        );

        /** @var DatedService[] $possibilities */
        $possibilities = [];

        foreach ($query_results as $entry) {
            for ($date = $from_date; $date->compare($to_date) <= 0; $date = $date->addDays(1)) {
                $skeleton_service = new ServiceCancellation(
                    $entry->uid
                    , $entry->period
                    , BankHoliday::from($entry->excludeBankHoliday->value)
                    , ShortTermPlanning::from($entry->shortTermPlanning->value)
                );
                if ($skeleton_service->runsOnDate($date)) {
                    $possibilities[] = new DatedService($skeleton_service, $date);
                }
            }
        }

        // filter out duplicate possibilities
        $possibilities_count = count($possibilities);
        foreach ($possibilities as $i => $possibility) {
            for ($j = $i + 1; $j < $possibilities_count; ++$j) {
                if (
                    $possibilities[$j] !== null
                    && $possibility->service->uid === $possibilities[$j]->service->uid
                    && $possibility->date->compare($possibilities[$j]->date) === 0
                ) {
                    $possibilities[$j] = null;
                }
            }
        }
        $possibilities = array_values(array_filter($possibilities));

        $real_services = $this->servicesCollection->find(
            [
                '$or' => array_map(
                    static fn(DatedService $dated_service) =>
                        [
                            'uid' => $dated_service->service->uid,
                            'period.from' => ['$lte' => $dated_service->date],
                            'period.to' => ['$gte' => $dated_service->date],
                        ]
                    , $possibilities
                )
            ] + ($permanent_only ? ['shortTermPlanning.value' => ShortTermPlanning::PERMANENT] : [])
        )->toArray();

        // replace skeleton services with real services - handle STP here
        foreach ($possibilities as &$possibility) {
            $result = null;
            foreach ($real_services as $service) {
                if (
                    $service->uid === $possibility->service->uid
                    && $service->runsOnDate($possibility->date)
                    && $service->isSuperior($result, $permanent_only)
                ) {
                    $result = $service;
                }
            }
            assert($result !== null);
            $possibility = new DatedService($result, $possibility->date);
        }
        unset($possibility);

        $results = [];
        // check calls in the results
        foreach ($possibilities as $possibility) {
            if ($possibility->service instanceof Service) {
                foreach ($possibility->service->points as $point) {
                    if ($point->location->crsCode === $crs) {
                        $time = $point->getTime($call_type, $time_type);
                        if ($time !== null) {
                            $date_time = $possibility->date->toDateTimeImmutable($time);
                            if ($date_time >= $from && $date_time < $to) {
                                $results[] = new ServiceCall($possibility, $point);
                            }
                        }
                    }
                }
            }
        }
        return $this->sortCallResults($results, $call_type, $time_type);
    }
}