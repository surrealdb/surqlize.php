<?php

declare(strict_types=1);

namespace Surqlize\Model;

use ReflectionClass;
use ReflectionProperty;
use Surqlize\Attributes\Cast;
use Surqlize\Attributes\Geometry;
use Surqlize\Attributes\Id;
use Surqlize\Attributes\Schema;
use Surqlize\Attributes\Search;
use Surqlize\Attributes\Vector;
use Surqlize\Model\Exception\MissingTableAttributeException;
use Surqlize\Query\Support\Exception\MissingTableNameAttributeException;
use Surqlize\Query\Support\TableNameResolver;
use Surqlize\Support\ClassString;

final class ModelMetadata
{
    /** @var array<class-string, self> */
    private static array $cache = [];

    /**
     * @param class-string<Model> $class
     * @param class-string<SchemaContract>|null $schemaClass
     * @param array<string, class-string<Model>> $casts
     * @param list<string> $properties
     * @param array<string, ReflectionProperty> $propertyReflections
	 * @param array<string, true> $propertyLookup
	 * @param array<string, string|null> $propertyTypes
     * @param array<string, 'search'|'vector'|'geometry'> $propertyFieldKinds
     */
    private function __construct(
        public readonly string $class,
        public readonly string $tableName,
        public readonly ?string $schemaClass,
        public readonly ?string $idProperty,
        public readonly array $casts,
        public readonly array $properties,
        public readonly array $propertyReflections,
		public readonly array $propertyLookup,
		public readonly array $propertyTypes,
        public readonly array $propertyFieldKinds,
    ) {}

    /**
     * @param class-string<Model> $class
     */
    public static function for(string $class): self
    {
        $class = ClassString::model($class);

        return self::$cache[$class] ??= self::resolve($class);
    }

    public static function clear(): void
    {
        self::$cache = [];
        ModelRegistry::clear();
    }

    /**
     * @param class-string<Model> $class
     */
    private static function resolve(string $class): self
    {
        $reflection = new ReflectionClass($class);

        $tableName = self::resolveTableName($reflection);
        $schemaClass = self::resolveSchemaAttribute($reflection);
        $idProperty = null;
        $casts = [];
        $properties = [];
        $propertyReflections = [];
		$propertyLookup = [];
		$propertyTypes = [];
        $propertyFieldKinds = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $name = $property->getName();
            $properties[] = $name;
            $propertyReflections[$name] = $property;
			$propertyLookup[$name] = true;
			$type = $property->getType();
			$propertyTypes[$name] = $type instanceof \ReflectionNamedType ? $type->getName() : null;

            if ($type instanceof \ReflectionNamedType && ! $type->isBuiltin()) {
                $typeName = $type->getName();

                if (is_subclass_of($typeName, Model::class)) {
                    /** @var class-string<Model> $typeName */
                    $casts[$name] = $typeName;
                }
            }

            foreach ($property->getAttributes(Id::class) as $attribute) {
                if ($idProperty !== null) {
                    throw new \InvalidArgumentException(
                        sprintf('Model "%s" defines multiple #[Id] properties.', $class),
                    );
                }

                $idProperty = $name;
            }

            foreach ($property->getAttributes(Cast::class) as $attribute) {
                /** @var Cast $cast */
                $cast = $attribute->newInstance();
                $casts[$name] = ClassString::model($cast->class, sprintf('Cast target for %s::$%s', $class, $name));
            }

            if ($property->getAttributes(Search::class) !== []) {
                $propertyFieldKinds[$name] = 'search';
            }

            if ($property->getAttributes(Vector::class) !== []) {
                $propertyFieldKinds[$name] = 'vector';
            }

            if ($property->getAttributes(Geometry::class) !== []) {
                $propertyFieldKinds[$name] = 'geometry';
            }
        }

        $metadata = new self(
            class: $class,
            tableName: $tableName,
            schemaClass: $schemaClass,
            idProperty: $idProperty,
            casts: $casts,
            properties: $properties,
            propertyReflections: $propertyReflections,
			propertyLookup: $propertyLookup,
			propertyTypes: $propertyTypes,
            propertyFieldKinds: $propertyFieldKinds,
        );

        ModelRegistry::register($metadata);

        return $metadata;
    }

    /** @param ReflectionClass<Model> $reflection */
    private static function resolveTableName(ReflectionClass $reflection): string
    {
        try {
            return TableNameResolver::resolve($reflection->getName());
        } catch (MissingTableNameAttributeException $exception) {
            throw new MissingTableAttributeException($reflection->getName(), $exception);
        }
    }

    /**
     * @param ReflectionClass<Model> $reflection
     *
     * @return class-string<SchemaContract>|null
     */
    private static function resolveSchemaAttribute(ReflectionClass $reflection): ?string
    {
        $attributes = $reflection->getAttributes(Schema::class);

        if ($attributes === []) {
            return null;
        }

        if (count($attributes) > 1) {
            throw new \InvalidArgumentException(
                sprintf('Model "%s" defines multiple #[Schema] attributes.', $reflection->getName()),
            );
        }

        /** @var Schema $schema */
        $schema = $attributes[0]->newInstance();

        return ClassString::schema($schema->class, sprintf('Schema target for model "%s"', $reflection->getName()));
    }
}
