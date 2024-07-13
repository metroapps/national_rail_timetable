<?php
declare(strict_types=1);

namespace App\Casts;

use App\Enums\Catering;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Caterings implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return array_map(
            static fn(string $char) => Catering::from($char),
            str_split((string)$value)
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
            throw new InvalidArgumentException(static::class . ' only supports casting from an array of Catering');
        }
        return implode('', array_map(
            static fn(Catering $catering) => $catering->value,
            $value
        ));
    }
}
