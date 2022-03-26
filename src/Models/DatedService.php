<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateTimeImmutable;

class DatedService {
    public function __construct(
        public readonly ServiceEntry $service
        , public readonly DateTimeImmutable $date
    ) {}
}