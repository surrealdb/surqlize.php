<?php

declare(strict_types=1);

namespace Surqlize\Query;

use SurrealDB\SDK\Contracts\QueryExecutor;
use SurrealDB\SDK\Query\BoundQuery;
use SurrealDB\SDK\Types\RecordId;
use SurrealDB\SDK\Types\Value;
use Surqlize\Connection\ConnectionManager;
use Surqlize\Model\Hydrator;
use Surqlize\Model\Model;
use Surqlize\Model\ModelMetadata;
use Surqlize\Query\Ast\WhereClause;
use Surqlize\Query\Ast\WhereCondition;
use Surqlize\Query\Compiler\Identifier;
use Surqlize\Query\Concerns\CompilesQueries;
use Surqlize\Query\Fields\FieldSet;
use Surqlize\Query\Fields\FieldSetRegistry;
use Surqlize\Query\Fields\TypedWhereResolver;
use Surqlize\Support\ClassString;

final class ModelMutationQuery implements CompilesQueries
{
    private const CREATE = 'CREATE';
    private const UPDATE = 'UPDATE';
    private const UPSERT = 'UPSERT';
    private const DELETE = 'DELETE';

    private ?WhereClause $where = null;

    /** @var 'CONTENT'|'MERGE'|'REPLACE'|'PATCH'|null */
    private ?string $mutation = null;

    private mixed $payload = null;

    /** @var 'NONE'|'BEFORE'|'AFTER'|'DIFF'|list<string>|array{value: string}|null */
    private string|array|null $returning = null;

    private ?string $timeout = null;

    /**
     * @param class-string<Model> $modelClass
     */
    private function __construct(
        private readonly string $action,
        private readonly string $modelClass,
        private readonly RecordId|string $target,
        private readonly FieldSet $fieldSet,
        private readonly ?QueryExecutor $executor = null,
    ) {}

    /**
     * @param class-string<Model> $modelClass
     * @param array<string, mixed> $data
     */
    public static function create(string $modelClass, array $data, ?RecordId $id = null, ?QueryExecutor $executor = null): self
    {
        $modelClass = ClassString::model($modelClass);
        $metadata = ModelMetadata::for($modelClass);

        return (new self(self::CREATE, $modelClass, $id ?? $metadata->tableName, FieldSetRegistry::resolve($modelClass), $executor))
            ->content($data)
            ->returnAfter();
    }

    /**
     * @param class-string<Model> $modelClass
     * @param array<string, mixed> $data
     */
    public static function update(string $modelClass, RecordId|string $target, array $data, ?QueryExecutor $executor = null): self
    {
        $modelClass = ClassString::model($modelClass);
        $query = (new self(self::UPDATE, $modelClass, $target, FieldSetRegistry::resolve($modelClass), $executor))
            ->returnAfter();

        return $data !== [] ? $query->merge($data) : $query;
    }

    /**
     * @param class-string<Model> $modelClass
     * @param array<string, mixed> $data
     */
    public static function upsert(string $modelClass, RecordId|string $target, array $data, ?QueryExecutor $executor = null): self
    {
        $modelClass = ClassString::model($modelClass);

        return (new self(self::UPSERT, $modelClass, $target, FieldSetRegistry::resolve($modelClass), $executor))
            ->content($data)
            ->returnAfter();
    }

    /**
     * @param class-string<Model> $modelClass
     */
    public static function delete(string $modelClass, RecordId|string $target, ?QueryExecutor $executor = null): self
    {
        $modelClass = ClassString::model($modelClass);

        return (new self(self::DELETE, $modelClass, $target, FieldSetRegistry::resolve($modelClass), $executor))
            ->returnBefore();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function content(array $data): self
    {
        return $this->withPayload('CONTENT', $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function merge(array $data): self
    {
        return $this->withPayload('MERGE', $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function replace(array $data): self
    {
        return $this->withPayload('REPLACE', $data);
    }

    /**
     * @param list<array<string, mixed>> $patches
     */
    public function patch(array $patches): self
    {
        return $this->withPayload('PATCH', $patches);
    }

    /**
     * @deprecated Passing string field names is kept for migration only. Prefer typed callbacks.
     *
     * @phpstan-param string|\Closure(FieldSet): (\Surqlize\Query\Ast\WherePredicate|list<\Surqlize\Query\Ast\WherePredicate>) $field
     */
    public function where(string|\Closure $field, Operator|string|null $operator = null, mixed $value = null): self
    {
        $clone = clone $this;
        $where = $clone->where ?? new WhereClause();
        $where = clone $where;

        if ($field instanceof \Closure) {
            foreach (TypedWhereResolver::resolveFor($this->fieldSet, $field) as $condition) {
                $where->add($condition);
            }

            $clone->where = $where;

            return $clone;
        }

        if ($operator === null) {
            throw new \InvalidArgumentException('Legacy string where() calls require an operator.');
        }

        $where->add(new WhereCondition($field, $operator, $value));
        $clone->where = $where;

        return $clone;
    }

    public function returnNone(): self
    {
        return $this->withReturning('NONE');
    }

    public function returnBefore(): self
    {
        return $this->withReturning('BEFORE');
    }

    public function returnAfter(): self
    {
        return $this->withReturning('AFTER');
    }

    public function returnDiff(): self
    {
        return $this->withReturning('DIFF');
    }

    /** @param string|list<string> $fields */
    public function returning(string|array $fields): self
    {
        return $this->withReturning(is_array($fields) ? $fields : [$fields]);
    }

    public function returningValue(string $field): self
    {
        return $this->withReturning(['value' => $field]);
    }

    public function timeout(int $amount, string $unit = 's'): self
    {
        if ($amount < 1) {
            throw new \InvalidArgumentException('Mutation TIMEOUT amount must be greater than zero.');
        }

        $unit = strtolower($unit);

        if (! in_array($unit, ['ns', 'us', 'ms', 's', 'm', 'h', 'w'], true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported TIMEOUT unit "%s".', $unit));
        }

        $clone = clone $this;
        $clone->timeout = $amount . $unit;

        return $clone;
    }

    public function compile(): string
    {
        $sql = $this->action . ' ' . $this->compileTarget();

        if ($this->mutation !== null) {
            $sql .= ' ' . $this->mutation . ' ' . Value::toSurql($this->payload);
        }

        if ($this->where !== null) {
            $compiledWhere = $this->where->compile();
            if ($compiledWhere !== '') {
                $sql .= ' ' . $compiledWhere;
            }
        }

        $sql .= $this->compileReturning();

        if ($this->timeout !== null) {
            $sql .= ' TIMEOUT ' . $this->timeout;
        }

        return $sql;
    }

    public function toBoundQuery(): BoundQuery
    {
        $query = new BoundQuery($this->action . ' ' . $this->compileTarget());

        if ($this->mutation !== null) {
            $query->append(' ' . $this->mutation . ' ' . $query->bind($this->payload));
        }

        if ($this->where !== null) {
            $compiledWhere = $this->where->compileBound($query);
            if ($compiledWhere !== '') {
                $query->append(' ' . $compiledWhere);
            }
        }

        $query->append($this->compileReturning());

        if ($this->timeout !== null) {
            $query->append(' TIMEOUT ' . $this->timeout);
        }

        return $query;
    }

    public function execute(): mixed
    {
        return $this->resolveExecutor()->query($this->toBoundQuery());
    }

    /**
     * @return list<Model>
     */
    public function executeModels(): array
    {
        $result = $this->execute();

        if (! is_array($result)) {
            throw new \RuntimeException(sprintf('Expected mutation result for "%s" to be a list; got %s.', $this->modelClass, get_debug_type($result)));
        }

        $hydrator = new Hydrator();
        $models = [];

        foreach ($result as $index => $row) {
            if (! is_array($row)) {
                throw new \RuntimeException(sprintf('Expected mutation row at index %d for "%s" to be an array; got %s.', $index, $this->modelClass, get_debug_type($row)));
            }

            $models[] = $hydrator->hydrate($this->modelClass, $row);
        }

        return $models;
    }

    public function firstModel(): ?Model
    {
        return $this->executeModels()[0] ?? null;
    }

    /** @param 'CONTENT'|'MERGE'|'REPLACE'|'PATCH' $mutation */
    private function withPayload(string $mutation, mixed $payload): self
    {
        if ($this->action === self::DELETE) {
            throw new \LogicException('DELETE mutations cannot include CONTENT, MERGE, REPLACE, or PATCH payloads.');
        }

        $clone = clone $this;
        $clone->mutation = $mutation;
        $clone->payload = $payload;

        return $clone;
    }

    /** @param 'NONE'|'BEFORE'|'AFTER'|'DIFF'|list<string>|array{value: string} $returning */
    private function withReturning(string|array $returning): self
    {
        $clone = clone $this;
        $clone->returning = $returning;

        return $clone;
    }

    private function compileTarget(): string
    {
        if ($this->target instanceof RecordId) {
            return $this->target->escape();
        }

        return Identifier::table($this->target, 'mutation target table');
    }

    private function compileReturning(): string
    {
        if ($this->returning === null) {
            return '';
        }

        if (is_string($this->returning)) {
            return ' RETURN ' . $this->returning;
        }

        if (isset($this->returning['value'])) {
            return ' RETURN VALUE ' . Identifier::field($this->returning['value'], 'RETURN VALUE field');
        }

        $fields = [];

        foreach ($this->returning as $index => $field) {
            $fields[] = Identifier::field($field, sprintf('RETURN field at index %d', $index));
        }

        return ' RETURN ' . implode(', ', $fields);
    }

    private function resolveExecutor(): QueryExecutor
    {
        return $this->executor ?? ConnectionManager::get();
    }
}
