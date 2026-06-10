<?php

declare(strict_types=1);

namespace Surqlize\Query\Concerns;

interface CompilesQueries
{
    public function compile(): string;
}
