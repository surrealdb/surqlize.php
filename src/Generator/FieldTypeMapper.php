<?php

declare(strict_types=1);

namespace Surqlize\Generator;

use ReflectionNamedType;
use ReflectionProperty;
use Surqlize\Model\ModelMetadata;
use Surqlize\Query\Fields\BooleanField;
use Surqlize\Query\Fields\Field;
use Surqlize\Query\Fields\NumericField;
use Surqlize\Query\Fields\RecordIdField;
use Surqlize\Query\Fields\StringField;

final class FieldTypeMapper
{
    /**
     * @return class-string<Field>
     */
    public function fieldClassFor(ReflectionProperty $property, ModelMetadata $metadata): string
    {
        if ($metadata->idProperty === $property->getName()) {
            return RecordIdField::class;
        }

        $type = $property->getType();

        if (! $type instanceof ReflectionNamedType) {
            return Field::class;
        }

        return match ($type->getName()) {
            'string' => StringField::class,
            'int', 'float' => NumericField::class,
            'bool' => BooleanField::class,
            default => Field::class,
        };
    }
}
