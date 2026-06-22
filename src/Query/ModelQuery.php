<?php

declare(strict_types=1);

namespace Surqlize\Query;

use SurrealDB\SDK\Contracts\QueryExecutor;
use SurrealDB\SDK\Query\BoundQuery;
use Surqlize\Query\Ast\FetchClause;
use Surqlize\Query\Ast\FieldSelection;
use Surqlize\Query\Ast\GraphTraversal;
use Surqlize\Query\Ast\OrderClause;
use Surqlize\Query\Ast\SelectStatement;
use Surqlize\Query\Ast\WhereClause;
use Surqlize\Query\Ast\WhereCondition;
use Surqlize\Query\Compiler\SurrealQlCompiler;
use Surqlize\Query\Concerns\CompilesQueries;
use Surqlize\Query\Fields\FieldSet;
use Surqlize\Query\Fields\FieldSetRegistry;
use Surqlize\Query\Fields\OrderDirection;
use Surqlize\Query\Fields\OrderExpression;
use Surqlize\Query\Fields\TypedFieldResolver;
use Surqlize\Query\Fields\TypedWhereResolver;
use Surqlize\Query\Compiler\Identifier;
use Surqlize\Query\Support\EdgeEndpointResolver;
use Surqlize\Model\Hydrator;
use Surqlize\Model\Model;
use Surqlize\Model\ModelMetadata;
use Surqlize\Model\ModelRegistry;
use Surqlize\Support\ClassString;

/**
 * @template TFields of \Surqlize\Query\Fields\FieldSet
 */
final class ModelQuery implements CompilesQueries
{
    private static ?SurrealQlCompiler $compiler = null;

    /**
     * @param class-string|string $modelClass
     * @param TFields $fieldSet
     */
    public function __construct(
        private readonly string $modelClass,
        private SelectStatement $ast,
        private readonly FieldSet $fieldSet,
        private readonly ?QueryExecutor $executor = null,
        private readonly ?string $tableOverride = null,
    ) {}

    /**
     * @param class-string $modelClass
     * @param list<string|GraphTraversal>|\Closure $fields
     *
     * @return self<FieldSet>
     */
    public static function for(string $modelClass, array|\Closure $fields): self
    {
        $modelClass = ClassString::model($modelClass);

        return self::forFieldSet($modelClass, FieldSetRegistry::resolve($modelClass), $fields);
    }

    /**
     * @template T of FieldSet
     * @param class-string $modelClass
     * @param T $fieldSet
     * @param list<string|GraphTraversal>|\Closure $fields
     * @phpstan-param list<string|GraphTraversal>|\Closure(T): (list<\Surqlize\Query\Fields\Field|string|GraphTraversal>|\Surqlize\Query\Fields\Field|string|GraphTraversal) $fields
     *
     * @return self<T>
     */
    public static function forFieldSet(string $modelClass, FieldSet $fieldSet, array|\Closure $fields): self
    {
        $modelClass = ClassString::model($modelClass);
        $fields = $fields instanceof \Closure
            ? TypedFieldResolver::resolveSelectionFor($fieldSet, $fields)
            : $fields;
        $containsGraph = self::fieldsContainGraph($fields);

        return new self(
            $modelClass,
            new SelectStatement(
                new FieldSelection($fields),
                $containsGraph ? null : ModelMetadata::for($modelClass)->tableName,
            ),
            $fieldSet,
        );
    }

    /**
     * @param list<string|GraphTraversal> $fields
     */
    private static function fieldsContainGraph(array $fields): bool
    {
        foreach ($fields as $field) {
            if ($field instanceof GraphTraversal) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param class-string $modelClass
     *
     * @return self<FieldSet>
     */
    public static function forValue(string $modelClass, string|\Closure $field): self
    {
        $modelClass = ClassString::model($modelClass);

        return self::forValueFieldSet($modelClass, FieldSetRegistry::resolve($modelClass), $field);
    }

    /**
     * @template T of FieldSet
     * @param class-string $modelClass
     * @param T $fieldSet
     * @phpstan-param string|\Closure(T): \Surqlize\Query\Fields\Field $field
     *
     * @return self<T>
     */
    public static function forValueFieldSet(string $modelClass, FieldSet $fieldSet, string|\Closure $field): self
    {
        $modelClass = ClassString::model($modelClass);
        $field = $field instanceof \Closure
            ? TypedFieldResolver::resolveValueFieldFor($fieldSet, $field)
            : $field;

        return new self(
            $modelClass,
            (new SelectStatement(
                new FieldSelection([$field]),
                ModelMetadata::for($modelClass)->tableName,
            ))->withValueField($field),
            $fieldSet,
        );
    }

    /**
     * @param class-string|null $modelClass
     * @param list<string|GraphTraversal>|\Closure $fields
     *
     * @return self<\Surqlize\Query\Fields\FieldSet>
     */
    public static function forTable(string $table, array|\Closure $fields, ?string $modelClass = null): self
    {
        $table = Identifier::table($table, 'table passed to ModelQuery::forTable()');
        $fieldContextClass = $modelClass !== null ? ClassString::model($modelClass) : null;

        if ($fields instanceof \Closure) {
            if ($fieldContextClass === null) {
                throw new \InvalidArgumentException('Typed table selects require a model class context.');
            }

            $fields = TypedFieldResolver::resolveSelection($fieldContextClass, $fields);
        }

        return new self(
            $modelClass ?? '',
            new SelectStatement(new FieldSelection($fields), $table),
            $fieldContextClass !== null ? FieldSetRegistry::resolve($fieldContextClass) : new FieldSet(),
            tableOverride: $table,
        );
    }

    /**
     * Edge in()/out() queries: SELECT from the endpoint model's table, not the edge table.
     *
     * {@see \Surqlize\Edge\EdgeQuery} should delegate here for `in()` / `out()` entry points.
     *
     * @param list<string|GraphTraversal>|\Closure $fields
     * @param class-string $edgeClass
     *
     * @return self<\Surqlize\Query\Fields\FieldSet>
     */
    public static function forEdgeEndpoint(
        string $edgeClass,
        EdgeDirection $direction,
        array|\Closure $fields = ['*'],
    ): self {
        $edgeClass = ClassString::edge($edgeClass);
        $endpointClass = EdgeEndpointResolver::endpointClass($edgeClass, $direction);
        $endpointTable = EdgeEndpointResolver::endpointTable($edgeClass, $direction);
        $fields = $fields instanceof \Closure
            ? TypedFieldResolver::resolveSelection($endpointClass, $fields)
            : $fields;

        return new self(
            $edgeClass,
            new SelectStatement(
                new FieldSelection($fields),
                $endpointTable,
            ),
            FieldSetRegistry::resolve($endpointClass),
            tableOverride: $endpointTable,
        );
    }

    public function modelClass(): string
    {
        return $this->modelClass;
    }

    public function table(): string
    {
        if ($this->tableOverride !== null) {
            return Identifier::table($this->tableOverride, 'ModelQuery table override');
        }

        if ($this->ast->fromTable() !== null) {
            return Identifier::table($this->ast->fromTable(), 'ModelQuery FROM table');
        }

        if (! class_exists($this->modelClass)) {
            throw new \LogicException(
                sprintf('Cannot resolve table for ModelQuery without a model class; current context is "%s".', $this->modelClass),
            );
        }

		return ModelMetadata::for(ClassString::model($this->modelClass))->tableName;
    }

    public function ast(): SelectStatement
    {
        return $this->ast;
    }

    /**
     * @param list<string|GraphTraversal>|\Closure $fields
     * @phpstan-param list<string|GraphTraversal>|\Closure(TFields): (list<\Surqlize\Query\Fields\Field|string|GraphTraversal>|\Surqlize\Query\Fields\Field|string|GraphTraversal) $fields
     *
     * @return self<TFields>
     */
    public function select(array|\Closure $fields): self
    {
        $fields = $fields instanceof \Closure
            ? TypedFieldResolver::resolveSelectionFor($this->fieldSet, $fields)
            : $fields;

        return $this->withAst($this->ast->withFields(new FieldSelection($fields)));
    }

    /**
     * @phpstan-param string|\Closure(TFields): \Surqlize\Query\Fields\Field $field
     *
     * @return self<TFields>
     */
    public function selectValue(string|\Closure $field): self
    {
        $field = $field instanceof \Closure
            ? TypedFieldResolver::resolveValueFieldFor($this->fieldSet, $field)
            : $field;

        return $this->withAst($this->ast->withValueField($field));
    }

    /**
     * @deprecated Passing string field names is kept for migration only. Prefer typed callbacks.
     *
     * @phpstan-param string|\Closure(TFields): (\Surqlize\Query\Ast\WhereCondition|list<\Surqlize\Query\Ast\WhereCondition>) $field
     *
     * @return self<TFields>
     */
    public function where(string|\Closure $field, Operator|string|null $operator = null, mixed $value = null): self
    {
        $where = $this->ast->where() ?? new WhereClause();
        $where = clone $where;

        if ($field instanceof \Closure) {
            foreach (TypedWhereResolver::resolveFor($this->fieldSet, $field) as $condition) {
                $where->add($condition);
            }

            return $this->withAst($this->ast->withWhere($where));
        }

        if ($operator === null) {
            throw new \InvalidArgumentException('Legacy string where() calls require an operator.');
        }

        $where->add(new WhereCondition($field, $operator, $value));

        return $this->withAst($this->ast->withWhere($where));
    }

    /**
     * @phpstan-param \Closure(TFields): (\Surqlize\Query\Fields\OrderExpression|list<\Surqlize\Query\Fields\OrderExpression>)|OrderExpression|string|list<OrderExpression> $order
     *
     * @return self<TFields>
     */
    public function orderBy(\Closure|OrderExpression|string|array $order, OrderDirection|string $direction = OrderDirection::Ascending): self
    {
        $orderClause = $this->ast->order() ?? new OrderClause();
        $orderClause = clone $orderClause;

        foreach ($this->resolveOrderExpressions($order, $direction) as $expression) {
            $orderClause->add($expression);
        }

        return $this->withAst($this->ast->withOrder($orderClause));
    }

    /**
     * @phpstan-param string|list<string>|\Closure(TFields): (\Surqlize\Query\Fields\Field|list<\Surqlize\Query\Fields\Field>) $fields
     *
     * @return self<TFields>
     */
    public function fetch(string|array|\Closure $fields): self
    {
        if ($fields instanceof \Closure) {
            $fields = TypedFieldResolver::resolveFetchFieldsFor($this->fieldSet, $fields);
        }

        $existing = $this->ast->fetch();
        $merged = $existing !== null
            ? [...$existing->fields(), ...(is_array($fields) ? $fields : [$fields])]
            : (is_array($fields) ? $fields : [$fields]);

        return $this->withAst($this->ast->withFetch(new FetchClause($merged)));
    }

    /**
     * @return self<TFields>
     */
    public function limit(int $limit): self
    {
        return $this->withAst($this->ast->withLimit($limit));
    }

    /**
     * @return self<TFields>
     */
    public function start(int $offset): self
    {
        return $this->withAst($this->ast->withStart($offset));
    }

    /**
     * Omit FROM clause (graph SELECT contract in architecture.md).
     *
     * @return self<TFields>
     */
    public function withoutFrom(): self
    {
        return $this->withAst($this->ast->withFromTable(null));
    }

    /**
     * @return self<TFields>
     */
    public function withExecutor(QueryExecutor $executor): self
    {
        return new self(
            $this->modelClass,
            $this->ast,
            $this->fieldSet,
            $executor,
            $this->tableOverride,
        );
    }

    public function compile(): string
    {
        return self::compiler()->compileSelect($this->ast);
    }

    public function toBoundQuery(): BoundQuery
    {
        return $this->ast->toBoundQuery();
    }

    private static function compiler(): SurrealQlCompiler
    {
        return self::$compiler ??= new SurrealQlCompiler();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function collect(): array
    {
        $executor = $this->resolveExecutor();
        $query = $this->toBoundQuery();
        $result = $executor->query($query);

        if (! is_array($result)) {
            throw new \RuntimeException(
                sprintf(
                    'Expected query executor to return a list of rows for model context "%s"; got %s while running: %s',
                    $this->modelClass !== '' ? $this->modelClass : 'table "' . $this->table() . '"',
                    get_debug_type($result),
                    $query->query,
                ),
            );
        }

        if ($this->ast->isSelectValue()) {
            return array_values($result);
        }

        foreach ($result as $index => $row) {
            if (! is_array($row)) {
                throw new \RuntimeException(
                    sprintf(
                        'Expected query row at index %s to be an array for model context "%s"; got %s while running: %s',
                        (string) $index,
                        $this->modelClass !== '' ? $this->modelClass : 'table "' . $this->table() . '"',
                        get_debug_type($row),
                        $query->query,
                    ),
                );
            }
        }

        return array_values($result);
    }

    /**
     * @return list<Model>
     */
    public function collectModels(): array
    {
        if ($this->ast->isSelectValue()) {
            throw new \LogicException('Cannot hydrate models from a SELECT VALUE query.');
        }

        $modelClass = $this->hydrationClass();
        $hydrator = new Hydrator();

        return array_map(
            static fn (array $row): Model => $hydrator->hydrate($modelClass, $row),
            $this->collect(),
        );
    }

    public function first(): mixed
    {
        $query = $this->limit(1);

        if ($this->ast->isSelectValue()) {
            return $query->collect()[0] ?? null;
        }

        return $query->collectModels()[0] ?? null;
    }

    /**
     * @return self<TFields>
     */
    private function withAst(SelectStatement $ast): self
    {
        return new self(
            $this->modelClass,
            $ast,
            $this->fieldSet,
            $this->executor,
            $this->tableOverride,
        );
    }

    /**
     * @param list<OrderExpression>|\Closure|OrderExpression|string $order
     *
     * @return list<OrderExpression>
     */
    private function resolveOrderExpressions(
        \Closure|OrderExpression|string|array $order,
        OrderDirection|string $direction,
    ): array {
        if ($order instanceof \Closure) {
            return TypedFieldResolver::resolveOrderFor($this->fieldSet, $order);
        }

        if ($order instanceof OrderExpression) {
            return [$order];
        }

        if (is_array($order)) {
            return $order;
        }

        return [new OrderExpression($order, $this->normalizeOrderDirection($direction))];
    }

    private function normalizeOrderDirection(OrderDirection|string $direction): OrderDirection
    {
        if ($direction instanceof OrderDirection) {
            return $direction;
        }

        return match (strtoupper($direction)) {
            'ASC', 'ASCENDING' => OrderDirection::Ascending,
            'DESC', 'DESCENDING' => OrderDirection::Descending,
            default => throw new \InvalidArgumentException(sprintf('Unknown order direction "%s".', $direction)),
        };
    }

    private function resolveExecutor(): QueryExecutor
    {
        if ($this->executor !== null) {
            return $this->executor;
        }

        if (class_exists(\Surqlize\Connection\ConnectionManager::class)) {
            $manager = \Surqlize\Connection\ConnectionManager::class;

            try {
                /** @var QueryExecutor $executor */
                $executor = $manager::get();
            } catch (\RuntimeException $exception) {
                throw new \RuntimeException(
                    sprintf(
                        'No QueryExecutor bound for ModelQuery context "%s". Pass one via withExecutor() or configure ConnectionManager::set() during bootstrap.',
                        $this->modelClass !== '' ? $this->modelClass : 'table "' . $this->table() . '"',
                    ),
                    previous: $exception,
                );
            }

            return $executor;
        }

        throw new \RuntimeException(
            sprintf(
                'No QueryExecutor bound for ModelQuery context "%s". Pass one via withExecutor() or configure ConnectionManager::set() during bootstrap.',
                $this->modelClass !== '' ? $this->modelClass : 'table "' . $this->table() . '"',
            ),
        );
    }

    /** @return class-string<Model> */
    private function hydrationClass(): string
    {
        $table = $this->table();
        $modelClass = ModelRegistry::resolve($table);

        if ($modelClass !== null) {
            return $modelClass;
        }

        if (is_subclass_of($this->modelClass, Model::class)) {
            /** @var class-string<Model> $modelClass */
            $modelClass = $this->modelClass;

            return $modelClass;
        }

        throw new \LogicException(
            sprintf('Cannot hydrate query results for table "%s"; no model is registered for that table.', $table),
        );
    }
}
