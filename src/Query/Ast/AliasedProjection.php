<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

use Surqlize\Query\Compiler\Identifier;

final readonly class AliasedProjection implements SelectProjection
{
    public function __construct(
        private string $field,
        private string $alias,
    ) {}

    public function compile(): string
    {
        return Identifier::selection($this->field, 'SELECT field')
            . ' AS '
            . Identifier::alias($this->alias, 'SELECT alias');
    }

    public function compileBound(): string
    {
        return $this->compile();
    }
}
