<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateTimeImmutable;
use Miklcct\NationalRailJourneyPlanner\Enums\ShortTermPlanning;

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

    /** @var array<string, ServiceEntry[]> */
    private array $services = [];

    /** @var array<string, ServiceEntry[]> */
    private array $associations = [];
}