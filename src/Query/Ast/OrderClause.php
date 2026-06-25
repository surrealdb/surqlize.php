<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

use Surqlize\Query\Fields\OrderExpression;

final class OrderClause implements Node
{
    /** @var list<OrderExpression> */
    private array $expressions;

    /** @param list<OrderExpression> $expressions */
    public function __construct(array $expressions = [])
    {
        $this->expressions = $expressions;
    }

    public function add(OrderExpression $expression): self
    {
        $this->expressions[] = $expression;

        return $this;
    }

    /** @return list<OrderExpression> */
    public function expressions(): array
    {
        return $this->expressions;
    }

    public function compile(): string
    {
        if ($this->expressions === []) {
            return '';
        }

        $sql = '';

        foreach ($this->expressions as $expression) {
            if ($sql !== '') {
                $sql .= ', ';
            }

            $sql .= $expression->compile();
        }

        return 'ORDER BY ' . $sql;
    }
}
