<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

class FixedLinks {
    public function __construct(
        array $fixedLinksByOriginCrs
        , array $fixedLinksByDestinationCrs
    ) {
        $this->fixedLinksByDestinationCrs = $fixedLinksByDestinationCrs;
        $this->fixedLinksByOriginCrs = $fixedLinksByOriginCrs;
    }

    /** @var array<string, array<string, FixedLink[]>> */
    public readonly array $fixedLinksByOriginCrs;
    /** @var array<string, array<string, FixedLink[]>> */
    public readonly array $fixedLinksByDestinationCrs;
}