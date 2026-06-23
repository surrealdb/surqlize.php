<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

interface SelectProjection extends Node
{
    public function compileBound(): string;
}
