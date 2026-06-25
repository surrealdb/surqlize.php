<?php

declare(strict_types=1);

namespace Surqlize\Query\Compiler;

use Surqlize\Query\Ast\Node;
use Surqlize\Query\Ast\SelectStatement;

final class SurrealQlCompiler
{
    public function compile(Node $node): string
    {
        return $node->compile();
    }

    public function compileSelect(SelectStatement $statement): string
    {
        return $statement->compile();
    }
}
