<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use Surqlize\Query\Ast\WhereCondition;
use Surqlize\Query\Operator;

/** Field reference for SurrealDB array fields. */
final class ArrayField extends Field
{
    /** @param list<mixed> $values */
    public function containsAll(array $values): WhereCondition
    {
        return $this->condition(Operator::CONTAINS_ALL, $values);
    }

    /** @param list<mixed> $values */
    public function containsAny(array $values): WhereCondition
    {
        return $this->condition(Operator::CONTAINS_ANY, $values);
    }

    /** @param list<mixed> $values */
    public function containsNone(array $values): WhereCondition
    {
        return $this->condition(Operator::CONTAINS_NONE, $values);
    }
}
