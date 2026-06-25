<?php

declare(strict_types=1);

namespace Surqlize\Support;

use Surqlize\Edge\Edge;
use Surqlize\Model\Model;
use Surqlize\Model\SchemaContract;
use Surqlize\Query\Fields\FieldSet;

final class ClassString
{
    /**
     * @return class-string
     */
    public static function existing(string $class, string $role = 'Class'): string
    {
        if (! class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('%s "%s" does not exist.', $role, $class));
        }

        return $class;
    }

    /**
     * @return class-string<Model>
     */
    public static function model(string $class, string $role = 'Model class'): string
    {
        self::existing($class, $role);

        if (! is_subclass_of($class, Model::class)) {
            throw new \InvalidArgumentException(sprintf('%s "%s" must extend %s.', $role, $class, Model::class));
        }

        /** @var class-string<Model> $class */
        return $class;
    }

    /**
     * @return class-string<Edge>
     */
    public static function edge(string $class, string $role = 'Edge class'): string
    {
        self::existing($class, $role);

        if (! is_subclass_of($class, Edge::class)) {
            throw new \InvalidArgumentException(sprintf('%s "%s" must extend %s.', $role, $class, Edge::class));
        }

        /** @var class-string<Edge> $class */
        return $class;
    }

    /**
     * @return class-string<SchemaContract>
     */
    public static function schema(string $class, string $role = 'Schema class'): string
    {
        self::existing($class, $role);

        if (! is_subclass_of($class, SchemaContract::class)) {
            throw new \InvalidArgumentException(sprintf('%s "%s" must implement %s.', $role, $class, SchemaContract::class));
        }

        /** @var class-string<SchemaContract> $class */
        return $class;
    }

    /**
     * @return class-string<FieldSet>
     */
    public static function fieldSet(string $class, string $role = 'Field set class'): string
    {
        self::existing($class, $role);

        if (! is_subclass_of($class, FieldSet::class)) {
            throw new \InvalidArgumentException(sprintf('%s "%s" must extend %s.', $role, $class, FieldSet::class));
        }

        /** @var class-string<FieldSet> $class */
        return $class;
    }
}
