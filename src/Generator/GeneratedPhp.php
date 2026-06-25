<?php

declare(strict_types=1);

namespace Surqlize\Generator;

final class GeneratedPhp
{
    public static function namespace(string $namespace): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/', $namespace) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid generated PHP namespace "%s".', $namespace));
        }

        return $namespace;
    }

    public static function stringLiteral(string $value): string
    {
        return var_export($value, true);
    }
}
