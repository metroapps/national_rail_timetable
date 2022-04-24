<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use Miklcct\NationalRailJourneyPlanner\Models\Points\TimingPoint;

class ServiceCall {
    public function __construct(
        public readonly DatedService $datedService
        , public readonly TimingPoint $call
    ) {}
}