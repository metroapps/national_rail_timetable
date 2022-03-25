<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

class Time {
    final public function __construct(
        public readonly int $hours
        , public readonly int $minutes
    ) {}

    public static function fromHhmm(string $hhmm) : static {
        return new static(
            (int)substr($hhmm, 0, 2)
            , (int)substr($hhmm, 2, 2)
        );
    }
}