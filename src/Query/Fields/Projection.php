<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use Surqlize\Query\Ast\ExpressionProjection;
use Surqlize\Query\Compiler\Identifier;

final class Projection
{
    public static function count(): ExpressionProjection
    {
        return new ExpressionProjection('count()');
    }

    public static function sum(Field|string $field): ExpressionProjection
    {
        return new ExpressionProjection('math::sum(' . self::fieldPath($field) . ')');
    }

    public static function mean(Field|string $field): ExpressionProjection
    {
        return new ExpressionProjection('math::mean(' . self::fieldPath($field) . ')');
    }

    public static function raw(string $expression): ExpressionProjection
    {
        return new ExpressionProjection($expression);
    }

    private static function fieldPath(Field|string $field): string
    {
        return Identifier::field($field instanceof Field ? $field->path() : $field, 'projection field');
    }
}
