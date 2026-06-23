<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

use SurrealDB\SDK\Query\BoundQuery;

interface WherePredicate
{
    public function compile(): string;

    public function compileBound(BoundQuery $query): string;
}
