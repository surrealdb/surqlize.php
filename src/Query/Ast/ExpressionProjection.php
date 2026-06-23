<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

use Surqlize\Query\Compiler\Identifier;

final readonly class ExpressionProjection implements SelectProjection
{
    public function __construct(
        private string $expression,
        private ?string $alias = null,
    ) {}

    public function as(string $alias): self
    {
        return new self($this->expression, $alias);
    }

    public function compile(): string
    {
        $sql = $this->expression;

        if ($this->alias !== null) {
            $sql .= ' AS ' . Identifier::alias($this->alias, 'SELECT expression alias');
        }

        return $sql;
    }

    public function compileBound(): string
    {
        return $this->compile();
    }
}
