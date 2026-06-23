<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

use SurrealDB\SDK\Query\BoundQuery;
use Surqlize\Query\Compiler\Identifier;
use Surqlize\Query\Compiler\ValueFormatter;
use Surqlize\Query\Operator;

final readonly class ExpressionWhereCondition implements WherePredicate
{
    public function __construct(
        private string $expression,
        private Operator|string $operator,
        private mixed $value,
    ) {}

    public function compile(): string
    {
        $operator = Identifier::operator($this->operator, 'WHERE expression operator');

        return sprintf('%s %s %s', $this->expression, $operator, ValueFormatter::format($this->value, $this->operator));
    }

    public function compileBound(BoundQuery $query): string
    {
        $operator = Identifier::operator($this->operator, 'WHERE expression operator');

        return sprintf('%s %s %s', $this->expression, $operator, $query->bind($this->value));
    }
}
