<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateInterval;
use DateTimeImmutable;
use Miklcct\NationalRailJourneyPlanner\Enums\ShortTermPlanning;
use function array_keys;

class Timetable {
    public function insertService(ServiceEntry $entry) : void {
        $this->services[$entry->uid][] = $entry;
    }

    public function insertAssociation(AssociationEntry $entry) : void {
        $this->associations[$entry->primaryUid][] = $entry;
        $this->associations[$entry->secondaryUid][] = $entry;
    }

    public function getUidOnDate(
        string $uid
        , DateTimeImmutable $date
        , bool $include_short_term_planning = true
    ) : ?ServiceEntry {
        $result = null;
        foreach ($this->services[$uid] ?? [] as $service) {
            if (
                ($include_short_term_planning
                    || $service->shortTermPlanning === ShortTermPlanning::PERMANENT)
                && ($result === null
                    || $service->shortTermPlanning !== ShortTermPlanning::PERMANENT)
                && $service->runsOnDate($date)
            ) {
                $result = $service;
            }
        }
        return $result;
    }

    /**
     * Get all public services which are active in the period specified
     *
     * @return array<string, Service[]> where the key is the departure date
     * in YYYY-MM-DD format
     */
    public function getServices(
        DateTimeImmutable $from
        , DateTimeImmutable $to
    ) : array {
        static $one_day = null;
        if ($one_day === null) {
            $one_day = new DateInterval('P1D');
        }
        $result = [];
        for (
            $date = $from->sub($one_day);
            $date < $to;
            $date = $date->add($one_day)
        ) {
            foreach (array_keys($this->services) as $uid) {
                $service = $this->getUidOnDate($uid, $date);
                if (
                    $service instanceof Service
                    && $service->serviceProperty->trainCategory
                        ->isPassengerTrain()
                ) {
                    $origin = $service->points[0];
                    $destination
                        = $service->points[count($service->points) - 1];
                    $departure_time = $origin->getPublicDeparture();
                    $arrival_time = $destination->getPublicArrival();
                    // We assume that the origin and destination of a passenger
                    // train is always for passenger use.
                    assert($departure_time instanceof Time);
                    assert($arrival_time instanceof Time);
                    if (
                        $arrival_time->getDateTimeOnDate($date) > $from
                        && $departure_time->getDateTimeOnDate($date) < $to
                    ) {
                        $result[$date->format('Y-m-d')][] = $service;
                    }
                }
            }
        }
        return $result;
    }

    /** @var array<string, ServiceEntry[]> */
    private array $services = [];

    /** @var array<string, ServiceEntry[]> */
    private array $associations = [];
}