<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Repositories;

use Miklcct\NationalRailJourneyPlanner\Enums\CallType;
use Miklcct\NationalRailJourneyPlanner\Models\AssociationEntry;
use Miklcct\NationalRailJourneyPlanner\Models\Date;
use Miklcct\NationalRailJourneyPlanner\Models\DatedAssociation;
use Miklcct\NationalRailJourneyPlanner\Models\DatedService;
use Miklcct\NationalRailJourneyPlanner\Models\FullService;
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

    /**
     * @param string[] $uids
     * @param Date $date
     * @param bool $three_days
     * @param bool $permanent_only
     * @return array<string, DatedService[]>
     */
    public function getServicesByUids(
        array $uids,
        Date $date,
        Date $to,
        bool $permanent_only = false
    ) : array;

    /**
     * Get all UIDs which calls / passes the station
     **
     * @return string[]
     */
    public function getUidsAtStation(string $crs, Date $from, Date $to, CallType $call_type) : array;

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
        , array $recursed_services = []
    ) : FullService;
}