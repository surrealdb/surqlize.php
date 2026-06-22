<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use Surqlize\Query\Ast\WhereCondition;
use Surqlize\Query\Compiler\Identifier;
use Surqlize\Query\Compiler\ValueFormatter;
use Surqlize\Query\Operator;

/** Field reference for SurrealDB geometry fields. */
final class GeometryField extends Field
{
    /** @param array<string, mixed>|list<float|int> $geometry */
    public function inside(array $geometry): WhereCondition
    {
        return $this->condition(Operator::INSIDE, $geometry);
    }

    /** @param array<string, mixed>|list<float|int> $geometry */
    public function intersects(array $geometry): WhereCondition
    {
        return $this->condition(Operator::INTERSECTS, $geometry);
    }

    /** @param array<string, mixed>|list<float|int> $point */
    public function distanceAsc(array $point): OrderExpression
    {
        return OrderExpression::expression($this->distanceExpression($point), OrderDirection::Ascending);
    }

    /** @param array<string, mixed>|list<float|int> $point */
    public function distanceDesc(array $point): OrderExpression
    {
        return OrderExpression::expression($this->distanceExpression($point), OrderDirection::Descending);
    }

    /** @param array<string, mixed>|list<float|int> $point */
    private function distanceExpression(array $point): string
    {
        return sprintf(
            'geo::distance(%s, %s)',
            Identifier::field($this->path(), 'geometry field'),
            ValueFormatter::format($point),
        );
    }
}
