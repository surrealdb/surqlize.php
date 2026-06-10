<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

interface Node
{
    /** Produce canonical SurrealQL fragment for this node. */
    public function compile(): string;
}
