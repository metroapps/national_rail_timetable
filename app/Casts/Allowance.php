<?php
declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\Time;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RangeException;

class Allowance implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): Time {
        if (!is_string($value)) {
            throw new InvalidArgumentException('The allowance value must be a string.');
        }

        return $value[1] === 'H' ? new Time(0, (int)$value[0], 30) : new Time(0, (int)$value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string {
        if (!$model instanceof Time) {
            throw new InvalidArgumentException('The allowance value must be a Time.');
        }

        if ($model->secondsFromOrigin === 0) {
            return null;
        }

        if ($model->negative) {
            throw new RangeException('The allowance value must not be negative.');
        }

        $minutes = $model->hours * Time::MINUTES_PER_HOUR + $model->minutes;
        if ($minutes > 99) {
            throw new RangeException('The allowance value must not be more than 99 minutes.');
        }

        if (!($model->seconds === 0 || $minutes <= 9 && $model->seconds === 30)) {
            throw new RangeException('The allowance value must be multiples of a minute for 10 minutes or more, or multiples of half-minutes for less than 10 minutes.');
        }

        return ($minutes === 0 ? ' ' : (string)$minutes) . ($model->seconds >= 30 ? 'H' : '');
    }
}
