<?php

declare(strict_types=1);

namespace Surqlize\Edge;

use Surqlize\Model\ModelMetadata;
use Surqlize\Query\Ast\GraphDirection;
use Surqlize\Query\Ast\GraphTraversal;
use Surqlize\Query\Ast\WherePredicate;
use Surqlize\Query\Fields\FieldSetRegistry;
use Surqlize\Query\Fields\TypedWhereResolver;

/**
 * Fluent builder for graph traversal SELECT fields; extends {@see GraphTraversal} AST node.
 */
final class GraphSelectField extends GraphTraversal
{
    private bool $shouldFetch = false;

    /** @param list<WherePredicate> $where */
    public function __construct(
        GraphDirection $direction,
        string $segment,
        array $where = [],
        ?GraphTraversal $next = null,
        ?string $alias = null,
        bool $shouldFetch = false,
    ) {
        parent::__construct($direction, $segment, $where, $next, $alias);
        $this->shouldFetch = $shouldFetch;
    }

    /** @param class-string $edgeClass */
    public static function fromEdge(string $edgeClass, GraphDirection $direction): self
    {
        return new self($direction, EdgeMetadata::for($edgeClass)->tableName);
    }

    /** @param class-string<\Surqlize\Model\Model> $modelClass */
    public function out(string $modelClass, ?callable $where = null): self
    {
        return $this->extend(GraphDirection::Out, $modelClass, $where);
    }

    /** @param class-string<\Surqlize\Model\Model> $modelClass */
    public function in(string $modelClass, ?callable $where = null): self
    {
        return $this->extend(GraphDirection::In, $modelClass, $where);
    }

    public function as(string $alias): self
    {
        return new self(
            $this->direction,
            $this->segment,
            $this->where,
            $this->withAliasOnLast($this->next, $alias),
            $this->alias,
            $this->shouldFetch,
        );
    }

    public function fetch(): self
    {
        return new self(
            $this->direction,
            $this->segment,
            $this->where,
            $this->next,
            $this->alias,
            true,
        );
    }

    public function shouldFetch(): bool
    {
        return $this->shouldFetch;
    }

    public function fetchField(): ?string
    {
        return $this->resolveAlias($this);
    }

    /** @param class-string<\Surqlize\Model\Model> $modelClass */
    private function extend(GraphDirection $direction, string $modelClass, ?callable $where): self
    {
        $table = ModelMetadata::for($modelClass)->tableName;
        $conditions = $where !== null ? $this->resolveWhere($where, $modelClass) : [];
        $segment = new GraphTraversal($direction, $table, $conditions);

        return new self(
            $this->direction,
            $this->segment,
            $this->where,
            $this->appendSegment($this->next, $segment),
            $this->alias,
            $this->shouldFetch,
        );
    }

    /**
     * @param class-string<\Surqlize\Model\Model> $modelClass
     *
     * @return list<WherePredicate>
     */
    private function resolveWhere(callable $where, string $modelClass): array
    {
        $closure = \Closure::fromCallable($where);

        if ($this->expectsLegacyGraphQuery($closure)) {
            $query = new GraphBracketQuery();
            $where($query);

            return $query->conditions();
        }

        $fields = FieldSetRegistry::resolve($modelClass);
        $result = $where($fields);

        return TypedWhereResolver::normalize($result, sprintf('graph traversal where callback for model "%s"', $modelClass));
    }

    private function expectsLegacyGraphQuery(\Closure $where): bool
    {
        $reflection = new \ReflectionFunction($where);
        $parameter = $reflection->getParameters()[0] ?? null;
        $type = $parameter?->getType();

        if (! $type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return false;
        }

        $name = $type->getName();

        return $name === GraphBracketQuery::class || is_a($name, GraphBracketQuery::class, true);
    }

    private function appendSegment(?GraphTraversal $head, GraphTraversal $segment): GraphTraversal
    {
        if ($head === null) {
            return $segment;
        }

        if ($head->next === null) {
            return new GraphTraversal(
                $head->direction,
                $head->segment,
                $head->where,
                $segment,
                $head->alias,
            );
        }

        return new GraphTraversal(
            $head->direction,
            $head->segment,
            $head->where,
            $this->appendSegment($head->next, $segment),
            $head->alias,
        );
    }

    private function resolveAlias(GraphTraversal $traversal): ?string
    {
        if ($traversal->next === null) {
            return $traversal->alias;
        }

        return $this->resolveAlias($traversal->next);
    }

    private function withAliasOnLast(?GraphTraversal $head, string $alias): ?GraphTraversal
    {
        if ($head === null) {
            return null;
        }

        if ($head->next === null) {
            return new GraphTraversal(
                $head->direction,
                $head->segment,
                $head->where,
                null,
                $alias,
            );
        }

        return new GraphTraversal(
            $head->direction,
            $head->segment,
            $head->where,
            $this->withAliasOnLast($head->next, $alias),
            $head->alias,
        );
    }
}
