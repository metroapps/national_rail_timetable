<?php
declare(strict_types=1);

namespace App\Casts;

use App\Enums\Activity;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Activities implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<Activity>
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        return array_map(
            static fn(string $char) => Activity::from(trim($char)),
            array_filter(
                array_map(
                    'trim',
                    str_split((string)$value, 2)
                ),
                static fn(string $value) => $value !== ''
            )
        );
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException(static::class . ' only supports casting from an array of Activity');
        }
        return implode('', array_map(
            static fn(Activity $activity) => str_pad($activity->value, 2),
            $value
        ));
    }
}
