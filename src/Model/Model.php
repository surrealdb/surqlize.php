<?php

declare(strict_types=1);

namespace Surqlize\Model;

use SurrealDB\SDK\Contracts\QueryExecutor;
use Surqlize\Model\Exception\ModelNotFoundException;
use Surqlize\Query\ModelQuery;
use Surqlize\Query\ModelMutationQuery;
use Surqlize\Query\Fields\FieldSet;
use Surqlize\Query\Fields\FieldSetRegistry;
use Surqlize\Query\Fields\Projection;
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
     * @phpstan-param list<string|\Surqlize\Query\Ast\GraphTraversal|\Surqlize\Query\Ast\SelectProjection>|\Closure(FieldSet): (list<\Surqlize\Query\Fields\Field|string|\Surqlize\Query\Ast\GraphTraversal|\Surqlize\Query\Ast\SelectProjection>|\Surqlize\Query\Fields\Field|string|\Surqlize\Query\Ast\GraphTraversal|\Surqlize\Query\Ast\SelectProjection) $fields
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
     * @return ModelQuery<FieldSet>
     */
    public static function query(): ModelQuery
    {
        return static::select(['*']);
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

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data, mixed $id = null, ?QueryExecutor $executor = null): static
    {
        /** @var class-string<static> $class */
        $class = static::class;

        (new ModelValidator())->validateData($class, $data);
        $model = self::createQuery($data, $id, $executor)->firstModel();

        if ($model instanceof static) {
            return $model;
        }

        /** @var static $fallback */
        $fallback = (new Hydrator())->hydrate($class, $data);

        return $fallback;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function createQuery(array $data, mixed $id = null, ?QueryExecutor $executor = null): ModelMutationQuery
    {
        /** @var class-string<static> $class */
        $class = static::class;
        $metadata = ModelMetadata::for($class);

        return ModelMutationQuery::create($class, $data, self::recordIdFrom($id, $metadata), $executor);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function upsert(array $data, mixed $id, ?QueryExecutor $executor = null): static
    {
        /** @var class-string<static> $class */
        $class = static::class;
        $metadata = ModelMetadata::for($class);
        $recordId = self::recordIdFrom($id, $metadata);

        if ($recordId === null) {
            throw new \InvalidArgumentException('upsert() requires a record id.');
        }

        (new ModelValidator())->validateData($class, $data);
        $model = ModelMutationQuery::upsert($class, $recordId, $data, $executor)->firstModel();

        if ($model instanceof static) {
            return $model;
        }

        /** @var static $fallback */
        $fallback = (new Hydrator())->hydrate($class, $data);

        return $fallback;
    }

    /**
     * @deprecated Passing string field names is kept for migration only. Prefer typed callbacks.
     *
     * @phpstan-param string|\Closure(FieldSet): (\Surqlize\Query\Ast\WhereCondition|list<\Surqlize\Query\Ast\WhereCondition>) $field
     */
    public static function updateWhere(string|\Closure $field, \Surqlize\Query\Operator|string|null $operator = null, mixed $value = null, ?QueryExecutor $executor = null): ModelMutationQuery
    {
        /** @var class-string<static> $class */
        $class = static::class;
        $metadata = ModelMetadata::for($class);

        return ModelMutationQuery::update($class, $metadata->tableName, [], $executor)
            ->where($field, $operator, $value);
    }

    /**
     * @deprecated Passing string field names is kept for migration only. Prefer typed callbacks.
     *
     * @phpstan-param string|\Closure(FieldSet): (\Surqlize\Query\Ast\WherePredicate|list<\Surqlize\Query\Ast\WherePredicate>) $field
     */
    public static function deleteWhere(string|\Closure $field, \Surqlize\Query\Operator|string|null $operator = null, mixed $value = null, ?QueryExecutor $executor = null): ModelMutationQuery
    {
        /** @var class-string<static> $class */
        $class = static::class;
        $metadata = ModelMetadata::for($class);

        return ModelMutationQuery::delete($class, $metadata->tableName, $executor)
            ->where($field, $operator, $value);
    }

    /**
     * @return list<static>
     */
    public static function all(?QueryExecutor $executor = null): array
    {
        $query = static::query();

        if ($executor !== null) {
            $query = $query->withExecutor($executor);
        }

        /** @var list<static> $models */
        $models = $query->collectModels();

        return $models;
    }

    public static function find(mixed $id, ?QueryExecutor $executor = null): ?static
    {
        /** @var class-string<static> $class */
        $class = static::class;
        $metadata = ModelMetadata::for($class);

        if ($metadata->idProperty === null) {
            throw new \LogicException(sprintf('Cannot find model "%s" because it has no #[Id] property.', $class));
        }

        $query = static::query()
            ->where(fn (FieldSet $fields) => $fields->field($metadata->idProperty)->eq(self::recordIdFrom($id, $metadata)))
            ->limit(1);

        if ($executor !== null) {
            $query = $query->withExecutor($executor);
        }

        $model = $query->first();

        return $model instanceof static ? $model : null;
    }

    public static function findOrFail(mixed $id, ?QueryExecutor $executor = null): static
    {
        return static::find($id, $executor) ?? throw new ModelNotFoundException(static::class, $id);
    }

    /**
     * @phpstan-param (\Closure(FieldSet): (\Surqlize\Query\Ast\WherePredicate|list<\Surqlize\Query\Ast\WherePredicate>))|null $where
     */
    public static function count(?\Closure $where = null, ?QueryExecutor $executor = null): int
    {
        $query = static::select([Projection::count()->as('count')])->groupAll();

        if ($where !== null) {
            $query = $query->where($where);
        }

        if ($executor !== null) {
            $query = $query->withExecutor($executor);
        }

        $row = $query->collect()[0] ?? null;

        return is_array($row) && isset($row['count']) ? (int) $row['count'] : 0;
    }

    /**
     * @phpstan-param (\Closure(FieldSet): (\Surqlize\Query\Ast\WherePredicate|list<\Surqlize\Query\Ast\WherePredicate>))|null $where
     */
    public static function exists(?\Closure $where = null, ?QueryExecutor $executor = null): bool
    {
        $query = static::select(['id'])->limit(1);

        if ($where !== null) {
            $query = $query->where($where);
        }

        if ($executor !== null) {
            $query = $query->withExecutor($executor);
        }

        return $query->collect() !== [];
    }

    public function save(?QueryExecutor $executor = null): static
    {
        $class = $this::class;
        $metadata = ModelMetadata::for($class);
        (new ModelValidator())->validateModel($this);

        $recordId = $this->recordIdForPersistence($metadata);
        $data = $this->persistenceData($metadata);

        $query = $recordId instanceof RecordId
            ? ModelMutationQuery::update($class, $recordId, $data, $executor)
            : ModelMutationQuery::create($class, $data, null, $executor);

        $model = $query->firstModel();

        if ($model instanceof static) {
            return $model;
        }

        return $this;
    }

    public function delete(?QueryExecutor $executor = null): mixed
    {
        $class = $this::class;
        $metadata = ModelMetadata::for($class);
        $recordId = $this->recordIdForPersistence($metadata);

        if (! $recordId instanceof RecordId) {
            throw new \LogicException(sprintf('Cannot delete model "%s" before its #[Id] property is a RecordId.', $class));
        }

        return ModelMutationQuery::delete($class, $recordId, $executor)->execute();
    }

    public function refresh(?QueryExecutor $executor = null): static
    {
        $metadata = ModelMetadata::for($this::class);
        $recordId = $this->recordIdForPersistence($metadata);

        if (! $recordId instanceof RecordId) {
            throw new \LogicException(sprintf('Cannot refresh model "%s" before its #[Id] property is a RecordId.', $this::class));
        }

        $fresh = static::findOrFail($recordId, $executor);

        foreach ($fresh->toArray() as $property => $value) {
            $this->{$property} = $fresh->{$property};
        }

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $metadata = ModelMetadata::for($this::class);
        $data = [];

        foreach ($metadata->properties as $property) {
            $reflection = new \ReflectionProperty($this, $property);

            if (! $reflection->isInitialized($this)) {
                continue;
            }

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

    /** @return array<string, mixed> */
    private function persistenceData(ModelMetadata $metadata): array
    {
        $data = $this->toArray();

        if ($metadata->idProperty !== null) {
            unset($data[$metadata->idProperty]);
        }

        return $data;
    }

    private function recordIdForPersistence(ModelMetadata $metadata): ?RecordId
    {
        $idProperty = $metadata->idProperty;

        if ($idProperty === null) {
            return null;
        }

        $reflection = new \ReflectionProperty($this, $idProperty);

        if (! $reflection->isInitialized($this)) {
            return null;
        }

        $id = $this->{$idProperty};

        return $id instanceof RecordId ? self::recordIdFrom($id, $metadata) : null;
    }

    private static function recordIdFrom(mixed $id, ModelMetadata $metadata): ?RecordId
    {
        if ($id === null) {
            return null;
        }

        if ($id instanceof RecordId) {
            if ($id->table !== $metadata->tableName) {
                throw new \InvalidArgumentException(sprintf('Expected record id for table "%s"; got "%s".', $metadata->tableName, $id->table));
            }

            return $id;
        }

        if (is_string($id) || is_int($id) || is_array($id) || is_object($id)) {
            return new RecordId($metadata->tableName, $id);
        }

        throw new \InvalidArgumentException(sprintf('Record id must be a string, integer, array, object, RecordId, or null; %s given.', get_debug_type($id)));
    }
}
