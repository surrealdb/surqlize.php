<?php

declare(strict_types=1);

namespace Surqlize\Query\Compiler;

use SurrealDB\SDK\Contracts\SurrealType;
use SurrealDB\SDK\Types\Value;
use Surqlize\Query\Operator;

final class ValueFormatter
{
    public static function format(mixed $value, Operator|string|null $operator = null): string
    {
        if ($value === null) {
            return 'NONE';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value instanceof SurrealType) {
            return $value->escape();
        }

        if ($value instanceof \DateTimeInterface) {
            return "d'" . $value->format('Y-m-d\TH:i:s.uP') . "'";
        }

        if (is_array($value)) {
            return Value::toSurql($value);
        }

        if (! is_string($value)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot compile value of type %s to SurrealQL literal.', get_debug_type($value)),
            );
        }

        $quote = self::quoteStyle($operator);

        return $quote . self::escape($value, $quote) . $quote;
    }

    private static function quoteStyle(Operator|string|null $operator): string
    {
        if ($operator instanceof Operator) {
            return match ($operator) {
                Operator::INCLUDES, Operator::CONTAINS, Operator::CONTAINS_ALL, Operator::CONTAINS_ANY, Operator::CONTAINS_NONE, Operator::LIKE, Operator::MATCHES => "'",
                default => '"',
            };
        }

        if (is_string($operator)) {
            $normalized = strtoupper($operator);

            return match ($normalized) {
                'INCLUDES', 'CONTAINS', 'CONTAINSALL', 'CONTAINSANY', 'CONTAINSNONE', 'LIKE', '@@' => "'",
                default => '"',
            };
        }

        return '"';
    }

    private static function escape(string $value, string $quote): string
    {
        return str_replace($quote, '\\' . $quote, $value);
    }
}
