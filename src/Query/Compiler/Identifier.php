<?php

declare(strict_types=1);

namespace Surqlize\Query\Compiler;

use Surqlize\Query\Operator;

final class Identifier
{
    private const SEGMENT = '[A-Za-z_][A-Za-z0-9_]*';

    /** @var list<string> */
    private const SYMBOL_OPERATORS = ['=', '!=', '>', '>=', '<', '<='];

    /** @var list<string> */
    private const WORD_OPERATORS = ['INCLUDES', 'CONTAINS', 'LIKE'];

    public static function table(string $identifier, string $context = 'Table name'): string
    {
        return self::assertIdentifier($identifier, $context);
    }

    public static function alias(string $identifier, string $context = 'Alias'): string
    {
        return self::assertIdentifier($identifier, $context);
    }

    public static function field(string $path, string $context = 'field path'): string
    {
        if ($path === '*') {
            throw new \InvalidArgumentException(sprintf('Wildcard "*" is not valid for %s.', $context));
        }

        return self::path($path, allowWildcard: false, context: $context);
    }

    public static function selection(string $path, string $context = 'SELECT field'): string
    {
        return self::path($path, allowWildcard: true, context: $context);
    }

    public static function operator(Operator|string $operator, string $context = 'WHERE operator'): string
    {
        if ($operator instanceof Operator) {
            return $operator->value;
        }

        if (in_array($operator, self::SYMBOL_OPERATORS, true)) {
            return $operator;
        }

        $normalized = strtoupper($operator);

        if (in_array($normalized, self::WORD_OPERATORS, true)) {
            return $normalized;
        }

        throw new \InvalidArgumentException(sprintf('Unsupported SurrealQL %s "%s".', $context, $operator));
    }

    private static function path(string $path, bool $allowWildcard, string $context): string
    {
        if ($allowWildcard && $path === '*') {
            return $path;
        }

        $pattern = '/^' . self::SEGMENT . '(?:\.' . self::SEGMENT . ')*$/';

        if (preg_match($pattern, $path) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid SurrealQL %s "%s".', $context, $path));
        }

        return $path;
    }

    private static function assertIdentifier(string $identifier, string $role): string
    {
        if (preg_match('/^' . self::SEGMENT . '$/', $identifier) !== 1) {
            throw new \InvalidArgumentException(sprintf('%s "%s" is not a safe SurrealQL identifier.', $role, $identifier));
        }

        return $identifier;
    }
}
