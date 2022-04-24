<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Repositories;

use DateTimeImmutable;
use Miklcct\NationalRailJourneyPlanner\Enums\CallType;
use Miklcct\NationalRailJourneyPlanner\Enums\TimeType;
use Miklcct\NationalRailJourneyPlanner\Models\AssociationEntry;
use Miklcct\NationalRailJourneyPlanner\Models\Date;
use Miklcct\NationalRailJourneyPlanner\Models\DatedAssociation;
use Miklcct\NationalRailJourneyPlanner\Models\DatedService;
use Miklcct\NationalRailJourneyPlanner\Models\FullService;
use Miklcct\NationalRailJourneyPlanner\Models\ServiceCall;
use Miklcct\NationalRailJourneyPlanner\Models\ServiceEntry;
use Miklcct\NationalRailJourneyPlanner\Models\Time;

interface ServiceRepositoryInterface {
    /**
     * @param ServiceEntry[] $services
     * @return void
     */
    public function insertServices(array $services) : void;

    /**
     * @param AssociationEntry[] $associations
     * @return void
     */
    public function insertAssociations(array $associations) : void;

    public function getService(string $uid, Date $date, bool $permanent_only = false) : ?DatedService;

    /**
     * Get all UIDs which calls / passes the station
     **
     * @return ServiceCall[]
     */
    public function getServicesAtStation(
        string $crs
        , DateTimeImmutable $from
        , DateTimeImmutable $to
        , CallType $call_type
        , TimeType $time_type = TimeType::PUBLIC
        , bool $permanent_only = false
    ) : array;

    /**
     * Get associations of the specified service
     *
     * If $from is specified, only the following associations happening after
     * it will be returned.
     * - joining another train
     * - dividing to form another train
     * - forming another service at the end
     *
     * If $to is specified, only the following associations happening before it
     * will be returned.
     * - another train joining
     * - dividing from another train
     * - formed from another service at the beginning
     *
     * @param DatedService $dated_service
     * @param Time|null $from
     * @param Time|null $to
     * @param bool $include_non_passenger
     * @return DatedAssociation[]
     */
    public function getAssociations(
        DatedService $dated_service
        , ?Time $from = null
        , ?Time $to = null
        , bool $include_non_passenger = false
        , bool $permanent_only = false
    ) : array;

    public function getFullService(
        DatedService $dated_service
        , ?Time $boarding = null
        , ?Time $alighting = null
        , bool $include_non_passenger = false
        , bool $permanent_only = false
        , array $recursed_services = []
    ) : FullService;

    /**
     * @param string $rsid
     * @param Date $date
     * @param bool $permanent_only
     * @return DatedService[]
     */
    public function getServiceByRsid(string $rsid, Date $date, bool $permanent_only = false) : array;
}