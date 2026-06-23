<?php

declare(strict_types=1);

namespace Surqlize\Generator;

use ReflectionNamedType;
use ReflectionProperty;
use SurrealDB\SDK\Types\RecordId;
use Surqlize\Model\Model;
use Surqlize\Model\ModelMetadata;
use Surqlize\Query\Fields\ArrayField;
use Surqlize\Query\Fields\BooleanField;
use Surqlize\Query\Fields\DateTimeField;
use Surqlize\Query\Fields\Field;
use Surqlize\Query\Fields\GeometryField;
use Surqlize\Query\Fields\ObjectField;
use Surqlize\Query\Fields\NumericField;
use Surqlize\Query\Fields\RecordIdField;
use Surqlize\Query\Fields\RecordLinkField;
use Surqlize\Query\Fields\SearchField;
use Surqlize\Query\Fields\StringField;
use Surqlize\Query\Fields\VectorField;

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

        $kind = $metadata->propertyFieldKinds[$property->getName()] ?? null;

        if ($kind !== null) {
            return match ($kind) {
                'search' => SearchField::class,
                'vector' => VectorField::class,
                'geometry' => GeometryField::class,
            };
        }

        $type = $property->getType();

        if (! $type instanceof ReflectionNamedType) {
            return Field::class;
        }

        $typeName = $type->getName();

        if (is_a($typeName, \DateTimeInterface::class, true)) {
            return DateTimeField::class;
        }

        if ($typeName === RecordId::class || is_subclass_of($typeName, Model::class)) {
            return RecordLinkField::class;
        }

        return match ($typeName) {
            'string' => StringField::class,
            'int', 'float' => NumericField::class,
            'bool' => BooleanField::class,
            'array' => ArrayField::class,
            'object' => ObjectField::class,
            default => Field::class,
        };
    }
}
