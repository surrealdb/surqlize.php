<?php

declare(strict_types=1);

namespace Surqlize\Model;

use Surqlize\Query\ModelQuery;
use Surqlize\Query\Fields\FieldSet;
use Surqlize\Query\Fields\FieldSetRegistry;
use Surqlize\Relate\RelateBuilder;
use SurrealDB\SDK\Types\RecordId;

abstract class Model
{
    public static function metadata(): ModelMetadata
    {
        /** @var class-string<static> $class */
        $class = static::class;

        return ModelMetadata::for($class);
    }

    /**
     * @param list<string|mixed>|\Closure $fields
     *
     * @return ModelQuery<FieldSet>
     */
    public static function select(array|\Closure $fields = ['*']): ModelQuery
    {
        /** @var class-string<static> $class */
        $class = static::class;

        return ModelQuery::for($class, $fields);
    }

    /**
     * @phpstan-param string|\Closure(FieldSet): \Surqlize\Query\Fields\Field $field
     *
     * @return ModelQuery<FieldSet>
     */
    public static function selectValue(string|\Closure $field): ModelQuery
    {
        /** @var class-string<static> $class */
        $class = static::class;

        return ModelQuery::forValue($class, $field);
    }

    public static function fields(): FieldSet
    {
        /** @var class-string<static> $class */
        $class = static::class;

        return FieldSetRegistry::resolve($class);
    }

    public static function relate(Model $from): RelateBuilder
    {
        /** @var class-string<static> $class */
        $class = static::class;

        return new RelateBuilder($class, $from);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $metadata = ModelMetadata::for($this::class);
        $data = [];

        foreach ($metadata->properties as $property) {
            $value = $this->{$property};

            if ($value instanceof Model) {
                $data[$property] = $value->toArray();
                continue;
            }

            if ($value instanceof RecordId) {
                $data[$property] = (string) $value;
                continue;
            }

            $data[$property] = $value;
        }

        return $data;
    }
}
