<?php
declare(strict_types=1);

namespace App\ValueObjects;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

final readonly class Time implements Castable {
    public const SECONDS_PER_MINUTE = 60;
    public const MINUTES_PER_HOUR = 60;
    public const HOURS_PER_DAY = 24;
    public const SECONDS_PER_DAY = self::SECONDS_PER_MINUTE * self::MINUTES_PER_HOUR * self::HOURS_PER_DAY;

    public int $secondsFromOrigin;
    public bool $negative;
    public int $hours;
    public int $minutes;
    public int $seconds;

    public function __construct(
        int $hours,
        int $minutes,
        int $seconds = 0,
        bool $negative = false,
    ) {
        $this->secondsFromOrigin =
            (($hours * self::MINUTES_PER_HOUR + $minutes) * self::SECONDS_PER_MINUTE + $seconds)
            * ($negative ? -1 : 1);
        $this->negative = $this->secondsFromOrigin < 0;
        $this->hours = intdiv(abs($this->secondsFromOrigin), self::SECONDS_PER_MINUTE * self::MINUTES_PER_HOUR);
        $this->minutes = intdiv(abs($this->secondsFromOrigin), self::SECONDS_PER_MINUTE) % self::MINUTES_PER_HOUR;
        $this->seconds = abs($this->secondsFromOrigin) % self::SECONDS_PER_MINUTE;
    }

    public static function fromString(string $time): self {
        if ($time[0] === '-') {
            return new self(0, 0, self::fromString(substr($time, 1))->secondsFromOrigin, true);
        }
        $components = explode(':', $time);
        return new self(...$components);
    }

    public function __toString(): string {
        return sprintf(
            '%s%02d:%02d:%02d',
            $this->negative ? '-' : '',
            $this->hours,
            $this->minutes,
            $this->seconds
        );
    }

    public function moduloDay(): self {
        $seconds = $this->secondsFromOrigin % self::SECONDS_PER_DAY;
        while ($seconds < 0) {
            $seconds += self::SECONDS_PER_DAY;
        }
        return new self(0, 0, $seconds);
    }

    public static function castUsing(array $arguments) : CastsAttributes {
        return new class implements CastsAttributes {
            public function get(Model $model, string $key, mixed $value, array $attributes) : Time {
                return Time::fromString($value);
            }
            public function set(Model $model, string $key, mixed $value, array $attributes) : string {
                return $model->__toString();
            }
        };
    }
}
