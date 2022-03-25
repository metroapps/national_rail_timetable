<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

class Time {
    final public function __construct(
        public readonly int $hours
        , public readonly int $minutes
        , public readonly bool $halfMinute
    ) {}

    public static function fromHhmm(
        string $hhmm
        , self $last_call = null
    ) : static {
        $result = new static(
            (int)substr($hhmm, 0, 2)
            , (int)substr($hhmm, 2, 2)
            , ($hhmm[4] ?? '') === 'H'
        );
        return $last_call !== null
            && $result->toHalfMinutes() < $last_call->toHalfMinutes()
            ? $result->addDay()
            : $result;
    }

    public function addDay() : static {
        return new static(
            $this->hours + 24
            , $this->minutes
            , $this->halfMinute
        );
    }

    public function toHalfMinutes() : int {
        return ($this->hours * 60 + $this->minutes) * 2 + $this->halfMinute;
    }
}