<?php

declare(strict_types=1);

namespace Surqlize\Edge;

use Surqlize\Query\EdgeDirection;
use Surqlize\Query\Fields\FieldSet;
use Surqlize\Query\ModelQuery;
use Surqlize\Query\Operator;
use Surqlize\Support\ClassString;
use SurrealDB\SDK\Contracts\QueryExecutor;

/**
 * Direction-aware SELECT entry for edge endpoint tables.
 *
 * Delegates to {@see ModelQuery::forEdgeEndpoint()} once fields are chosen via
 * {@see select()} / {@see selectValue()}.
 */
final class EdgeQuery
{
    /** @var ModelQuery<FieldSet>|null */
    private ?ModelQuery $query = null;

    /**
     * @param class-string $edgeClass
     */
    private function __construct(
        private readonly string $edgeClass,
        private readonly EdgeDirection $direction,
        private readonly ?QueryExecutor $executor = null,
    ) {}

    /**
     * @param class-string $edgeClass
     */
    public static function forDirection(
        string $edgeClass,
        EdgeDirection $direction,
        ?QueryExecutor $executor = null,
    ): self {
        $edgeClass = ClassString::edge($edgeClass);

        return new self($edgeClass, $direction, $executor);
    }

    /**
     * @param list<string|\Surqlize\Query\Ast\GraphTraversal>|\Closure $fields
     *
     * @return ModelQuery<FieldSet>
     */
    public function select(array|\Closure $fields): ModelQuery
    {
        $query = ModelQuery::forEdgeEndpoint($this->edgeClass, $this->direction, $fields);

        if ($this->executor !== null) {
            $query = $query->withExecutor($this->executor);
        }

        $this->query = $query;

        return $query;
    }

    /** @return ModelQuery<FieldSet> */
    public function selectValue(string|\Closure $field): ModelQuery
    {
        $query = ModelQuery::forEdgeEndpoint($this->edgeClass, $this->direction, ['*'])
            ->selectValue($field);

        if ($this->executor !== null) {
            $query = $query->withExecutor($this->executor);
        }

        $this->query = $query;

        return $query;
    }

    /**
     * @deprecated Passing string field names is kept for migration only. Prefer typed callbacks after select().
     *
     * @return ModelQuery<FieldSet>
     */
    public function where(string|\Closure $field, Operator|string|null $operator = null, mixed $value = null): ModelQuery
    {
        return $this->requireQuery()->where($field, $operator, $value);
    }

    /**
     * @param list<string>|string|\Closure $fields
     *
     * @return ModelQuery<FieldSet>
     */
    public function fetch(array|string|\Closure $fields): ModelQuery
    {
        return $this->requireQuery()->fetch($fields);
    }

    public function compile(): string
    {
        return $this->requireQuery()->compile();
    }

    /**
     * @return array<int, mixed>
     */
    public function collect(): array
    {
        return $this->requireQuery()->collect();
    }

    /** @return ModelQuery<FieldSet> */
    private function requireQuery(): ModelQuery
    {
        if ($this->query === null) {
            throw new \LogicException(
                sprintf(
                    'Cannot build edge query for "%s" in %s direction before fields are selected. Call select([...]) or selectValue(...) first.',
                    $this->edgeClass,
                    $this->direction->name,
                ),
            );
        }

        return $this->query;
    }
}
