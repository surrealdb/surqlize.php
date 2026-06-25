<?php

declare(strict_types=1);

namespace Surqlize\Edge;

use Surqlize\Query\Ast\WhereCondition;
use Surqlize\Query\Operator;

/**
 * Lightweight WHERE builder passed to graph traversal callables.
 *
 * @internal
 */
final class GraphBracketQuery
{
    /** @var list<WhereCondition> */
    private array $conditions = [];

    public function where(string $field, Operator|string $operator, mixed $value): self
    {
        $this->conditions[] = new WhereCondition($field, $operator, $value);

        return $this;
    }

    /** @return list<WhereCondition> */
    public function conditions(): array
    {
        return $this->conditions;
    }
}
