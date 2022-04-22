<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use BackedEnum;
use Miklcct\NationalRailJourneyPlanner\Attributes\ElementType;
use ReflectionClass;
use ReflectionNamedType;
use function is_a;
use function method_exists;

// this trait needs to be included in every class in a hierarchy
// due to the way bsonUnserialize works in regard to readonly property
trait BsonSerializeTrait {
    public function bsonSerialize() : array {
        return (array)$this;
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
                $is_backed_enum = $type instanceof ReflectionNamedType
                    && is_a($type->getName(), BackedEnum::class, true);
                if ($type->getName() === 'array') {
                    foreach ($property->getAttributes() as $attribute) {
                        $instance = $attribute->newInstance();
                        if ($instance instanceof ElementType && is_a($instance->type, BackedEnum::class, true)) {
                            foreach ($data[$key] as &$value) {
                                $value = $instance->type::from($value->value);
                            }
                            unset($value);
                        }
                    }
                }
                /** @noinspection PhpVariableVariableInspection */
                /** @noinspection PhpUndefinedMethodInspection */
                $this->$key = $is_backed_enum ? $type->getName()::from($data[$key]->value) : $data[$key];
            }
        }
    }
}