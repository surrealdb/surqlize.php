<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use Surqlize\Query\Ast\ExpressionProjection;
use Surqlize\Query\Ast\WhereCondition;
use Surqlize\Query\Compiler\ValueFormatter;
use Surqlize\Query\Operator;

/** Field reference for schema-backed full-text search fields. */
final class SearchField extends Field
{
    public function matches(string $query): WhereCondition
    {
        return $this->condition(Operator::MATCHES, $query);
    }

    public function score(int $matchRef = 1): ExpressionProjection
    {
        return new ExpressionProjection(sprintf('search::score(%d)', $matchRef));
    }

    public function highlight(string $prefix = '<b>', string $suffix = '</b>', int $matchRef = 1): ExpressionProjection
    {
        return new ExpressionProjection(sprintf(
            'search::highlight(%s, %s, %d)',
            ValueFormatter::format($prefix),
            ValueFormatter::format($suffix),
            $matchRef,
        ));
    }
}
