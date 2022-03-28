<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Repositories;

use DateTimeImmutable;
use Miklcct\NationalRailJourneyPlanner\Models\AssociationEntry;
use Miklcct\NationalRailJourneyPlanner\Models\DatedAssociation;
use Miklcct\NationalRailJourneyPlanner\Models\DatedService;
use Miklcct\NationalRailJourneyPlanner\Models\DestinationPoint;
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

    public function getUidOnDate(
        string $uid,
        DateTimeImmutable $date,
        bool $permanent_only = false
    ) : ?ServiceEntry;

    /**
     * Get all public services which are active in the period specified
     *
     * @return DatedService[]
     */
    public function getServices(
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        bool $include_non_passenger = false
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
     * @return DatedAssociation[]
     */
    public function getAssociations(
        DatedService $dated_service,
        ?Time $from = null,
        ?Time $to = null,
        bool $include_non_passenger = false
    ) : array;

    /**
     * Get the "real" destination of the train, taking joins and splits into account.
     *
     * @param DatedService $dated_service
     * @param ?Time $time
     * @return DestinationPoint[]
     */
    public function getRealDestinations(DatedService $dated_service, ?Time $time = null) : array;
}