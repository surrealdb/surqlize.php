<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

use Surqlize\Query\Compiler\Identifier;

final readonly class FieldProjection implements SelectProjection
{
    public function __construct(
        private string $field,
    ) {}

    public function compile(): string
    {
        return Identifier::selection($this->field, 'SELECT field');
    }

    public function compileBound(): string
    {
        return $this->compile();
    }
}
