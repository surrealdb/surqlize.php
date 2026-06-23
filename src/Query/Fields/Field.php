<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use Surqlize\Query\Ast\AliasedProjection;
use Surqlize\Query\Ast\WhereCondition;
use Surqlize\Query\Operator;

class Field implements \Stringable
{
    public function __construct(
        private readonly string $path,
    ) {}

    public function path(): string
    {
        return $this->path;
    }

    public function eq(mixed $value): WhereCondition
    {
        return $this->condition(Operator::EQUALS, $value);
    }

    public function notEq(mixed $value): WhereCondition
    {
        return $this->condition(Operator::NOT_EQUALS, $value);
    }

    public function gt(mixed $value): WhereCondition
    {
        return $this->condition(Operator::GREATER_THAN, $value);
    }

    public function gte(mixed $value): WhereCondition
    {
        return $this->condition(Operator::GREATER_THAN_OR_EQUAL, $value);
    }

    public function lt(mixed $value): WhereCondition
    {
        return $this->condition(Operator::LESS_THAN, $value);
    }

    public function lte(mixed $value): WhereCondition
    {
        return $this->condition(Operator::LESS_THAN_OR_EQUAL, $value);
    }

    public function includes(mixed $value): WhereCondition
    {
        return $this->condition(Operator::INCLUDES, $value);
    }

    public function contains(mixed $value): WhereCondition
    {
        return $this->condition(Operator::CONTAINS, $value);
    }

    public function like(string $value): WhereCondition
    {
        return $this->condition(Operator::LIKE, $value);
    }

    public function asc(): OrderExpression
    {
        return new OrderExpression($this->path, OrderDirection::Ascending);
    }

    public function desc(): OrderExpression
    {
        return new OrderExpression($this->path, OrderDirection::Descending);
    }

    public function as(string $alias): AliasedProjection
    {
        return new AliasedProjection($this->path, $alias);
    }

    public function condition(Operator|string $operator, mixed $value): WhereCondition
    {
        return new WhereCondition($this->path, $operator, $value);
    }

    public function __toString(): string
    {
        return $this->path;
    }
}
