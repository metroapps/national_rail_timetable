<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Repositories;

use DateTimeImmutable;
use Miklcct\NationalRailJourneyPlanner\Enums\BankHoliday;
use Miklcct\NationalRailJourneyPlanner\Enums\CallType;
use Miklcct\NationalRailJourneyPlanner\Enums\ShortTermPlanning;
use Miklcct\NationalRailJourneyPlanner\Enums\TimeType;
use Miklcct\NationalRailJourneyPlanner\Models\CallCacheGroup;
use Miklcct\NationalRailJourneyPlanner\Models\CallCacheItem;
use Miklcct\NationalRailJourneyPlanner\Models\Date;
use Miklcct\NationalRailJourneyPlanner\Models\DatedService;
use Miklcct\NationalRailJourneyPlanner\Models\Service;
use Miklcct\NationalRailJourneyPlanner\Models\ServiceCall;
use Miklcct\NationalRailJourneyPlanner\Models\ServiceEntry;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use stdClass;
use function array_chunk;
use function array_filter;
use function array_map;
use function array_values;
use function preg_quote;

class MongodbServiceRepository extends AbstractServiceRepository {
    public function __construct(
        private readonly Collection $servicesCollection
        , private readonly Collection $associationsCollection
        , private readonly ?Collection $callCacheCollection
        , bool $permanentOnly = false
    ) {
        parent::__construct($permanentOnly);
    }

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
                ['key' => ['serviceProperty.rsid' => 1]],
                ['key' => ['points.servicePropertyChange.rsid' => 1]],
            ]
        );
        $this->associationsCollection->createIndexes(
            [
                ['key' => ['primaryUid' => 1]],
                ['key' => ['secondaryUid' => 1]],
            ]
        );
    }

    public function getService(string $uid, Date $date) : ?DatedService {
        $query_results = $this->servicesCollection->find(
            [
                'uid' => $uid,
                'period.from' => ['$lte' => $date],
                'period.to' => ['$gte' => $date],
            ] + ($this->permanentOnly ? ['shortTermPlanning.value' => ShortTermPlanning::PERMANENT->value] : [])
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

    public function getServiceByRsid(string $rsid, Date $date) : array {
        $predicate = match(strlen($rsid)) {
            6 => new Regex(sprintf('^%s\d{2,2}$', preg_quote($rsid, null)), 'i'),
            8 => $rsid,
        };

        // find the UID first
        $uids = array_values(
            array_unique(
                array_map(
                    static fn(stdClass $object) => $object->uid
                    , $this->servicesCollection->find(
                    [
                            'period.from' => ['$lte' => $date],
                            'period.to' => ['$gte' => $date],
                            '$or' => [
                                ['serviceProperty.rsid' => $predicate],
                                ['points.servicePropertyChange.rsid' => $predicate],
                            ],
                        ] + ($this->permanentOnly ? ['shortTermPlanning.value' => ShortTermPlanning::PERMANENT->value] : [])
                        , [
                            'projection' => ['uid' => 1, '_id' => 0]
                        ]
                    )->toArray()
                )
            )
        );
        return $this->findServicesInUidMatchingRsid($uids, $rsid, $date);
    }

    public function getServicesAtStation(
        string $crs,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        CallType $call_type,
        TimeType $time_type = TimeType::PUBLIC
    ) : array {
        $cache_entry = $this->callCacheCollection?->findOne(
            [
                'crs' => $crs,
                'from' => new UTCDateTime($from),
                'to' => new UTCDateTime($to),
                'callType.value' => $call_type->value,
                'timeType.value' => $time_type->value,
            ]
        );
        if ($cache_entry !== null) {
            return $this->getServiceCallsFromCache($cache_entry);
        }

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
            ] + ($this->permanentOnly ? ['shortTermPlanning.value' => ShortTermPlanning::PERMANENT->value] : [])
            , ['projection' => ['uid' => 1, '_id' => 0, 'period' => 1, 'excludeBankHoliday' => 1, 'shortTermPlanning' => 1]]
        );

        /** @var DatedService[] $possibilities */
        $possibilities = [];

        foreach ($query_results as $entry) {
            for ($date = $from_date; $date->compare($to_date) <= 0; $date = $date->addDays(1)) {
                $skeleton_service = new ServiceEntry(
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
        /** @var DatedService[] $possibilities */
        $possibilities = array_values(array_filter($possibilities));

        if ($possibilities === []) {
            return [];
        }
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
            ] + ($this->permanentOnly ? ['shortTermPlanning.value' => ShortTermPlanning::PERMANENT->value] : [])
        )->toArray();

        // replace skeleton services with real services - handle STP here
        foreach ($possibilities as &$possibility) {
            $result = null;
            foreach ($real_services as $service) {
                if (
                    $service->uid === $possibility->service->uid
                    && $service->runsOnDate($possibility->date)
                    && $service->isSuperior($result, $this->permanentOnly)
                ) {
                    $result = $service;
                }
            }
            assert($result !== null);
            $possibility = new DatedService($result, $possibility->date);
        }
        unset($possibility);

        /** @var ServiceCall[] */
        $results = array_merge(
            ...array_map(
                static fn(DatedService $possibility) =>
                    $possibility->service instanceof Service
                        ? $possibility->getCallsAt($crs, $call_type, $time_type, $from, $to)
                        : []
                , $possibilities
            )
        );
        $results = $this->sortCallResults($results, $call_type, $time_type);
        if ($this->callCacheCollection !== null) {
            $cache_items = array_map(
                static function (ServiceCall $service_call) use ($call_type, $time_type) : CallCacheItem {
                    $service = $service_call->datedService->service;
                    assert($service instanceof Service);
                    $time = $service_call->call->getTime($call_type, $time_type);
                    assert($time !== null);
                    return new CallCacheItem(
                        $service_call->datedService->date->toDateTimeImmutable(
                            $time
                        )
                        , new ServiceCall(
                            new DatedService(
                                new ServiceEntry(
                                    $service->uid
                                    , $service->period
                                    , $service->excludeBankHoliday
                                    , $service->shortTermPlanning
                                )
                                , $service_call->datedService->date
                            )
                            , $service_call->call
                        )
                        , $service->getServicePropertyAtTime($time)
                    );
                }
                , $results
            );
            $cache_document = new CallCacheGroup($crs, $from, $to, $call_type, $time_type, $cache_items);
            $this->callCacheCollection->insertOne($cache_document);
        }
        return $results;
    }

    /** @return ServiceCall[] */
    private function getServiceCallsFromCache(CallCacheGroup $group) : array {
        if ($group->calls === []) {
            return [];
        }
        $predicate = [
            '$or' => array_map(
                static fn(CallCacheItem $item) =>
                    [
                        'uid' => $item->serviceCall->datedService->service->uid,
                        'period' => $item->serviceCall->datedService->service->period,
                        'shortTermPlanning.value' => $item->serviceCall->datedService->service->shortTermPlanning->value,
                    ]
                , $group->calls
            )
        ];
        $services = $this->servicesCollection->find($predicate)->toArray();
        return array_map(
            static function (CallCacheItem $cache_item) use ($services) : ServiceCall {
                $skeleton_service = $cache_item->serviceCall->datedService->service;
                $real_service = array_values(
                    array_filter(
                        $services
                        , static fn(ServiceEntry $service) =>
                            $service->uid === $skeleton_service->uid
                            && $service->period == $skeleton_service->period
                            && $service->shortTermPlanning === $skeleton_service->shortTermPlanning
                    )
                );
                return new ServiceCall(
                    new DatedService($real_service[0], $cache_item->serviceCall->datedService->date)
                    , $cache_item->serviceCall->call
                );
            }
            , $group->calls
        );
    }
}