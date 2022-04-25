<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use BackedEnum;
use DateTimeInterface;
use Miklcct\NationalRailJourneyPlanner\Attributes\ElementType;
use MongoDB\BSON\UTCDateTime;
use ReflectionClass;
use UnexpectedValueException;
use function is_a;
use function method_exists;

// this trait needs to be included in every class in a hierarchy
// due to the way bsonUnserialize works in regard to readonly property
trait BsonSerializeTrait {
    public function bsonSerialize() : array {
        return array_map(
            static fn($value) => $value instanceof DateTimeInterface ? new UTCDateTime($value) : $value
            , (array)$this
        );
    }

    public function bsonUnserialize(array $data) : void {
        $class = new ReflectionClass(self::class);
        if ($class->getParentClass() !== false && method_exists(parent::class, 'bsonUnserialize')) {
            parent::bsonUnserialize($data);
        }
        foreach ($class->getProperties() as $property) {
            $declaring_class_name = $property->getDeclaringClass()->getName();
            if ($property->isPublic() && !$property->isStatic() && $declaring_class_name === self::class) {
                $key = $property->name;
                $type = $property->getType();
                if ($type->getName() === 'array') {
                    foreach ($property->getAttributes() as $attribute) {
                        $instance = $attribute->newInstance();
                        if ($instance instanceof ElementType) {
                            foreach ($data[$key] as &$value) {
                                $value = self::processValue($instance->type, $value);
                            }
                            unset($value);
                        }
                    }
                }
                /** @noinspection PhpVariableVariableInspection */
                $this->$key = self::processValue($type->getName(), $data[$key]);
            }
        }
    }

    private static function processValue(string $type, mixed $value) : mixed {
        if (is_a($type, BackedEnum::class, true)) {
            return $type::tryFrom($value->value);
        }
        if (is_a($type, DateTimeInterface::class, true)) {
            if (!$value instanceof UTCDateTime) {
                throw new UnexpectedValueException('Only BSON UTCDateTime can be loaded into DateTimeInterface');
            }
            return $type::createFromInterface($value->toDateTime());
        }
        return $value;
    }
}