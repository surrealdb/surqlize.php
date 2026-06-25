<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

enum GraphDirection: string
{
    case Out = '->';
    case In = '<-';

    public function arrow(): string
    {
        return $this->value;
    }
}
