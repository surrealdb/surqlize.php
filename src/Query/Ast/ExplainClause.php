<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

final readonly class ExplainClause implements Node
{
    public function __construct(
        private bool $full = false,
    ) {}

    public function compile(): string
    {
        return $this->full ? 'EXPLAIN FULL' : 'EXPLAIN';
    }
}
