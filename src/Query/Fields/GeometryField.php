<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use Surqlize\Query\Ast\ExpressionProjection;
use Surqlize\Query\Ast\ExpressionWhereCondition;
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

    /** @param array<string, mixed>|list<float|int> $geometry */
    public function containsGeometry(array $geometry): WhereCondition
    {
        return $this->condition(Operator::CONTAINS, $geometry);
    }

    /** @param array<string, mixed>|list<float|int> $point */
    public function withinMeters(array $point, int|float $meters): ExpressionWhereCondition
    {
        return new ExpressionWhereCondition($this->distanceExpression($point), Operator::LESS_THAN_OR_EQUAL, $meters);
    }

    /** @param array<string, mixed>|list<float|int> $point */
    public function distanceTo(array $point): ExpressionProjection
    {
        return new ExpressionProjection($this->distanceExpression($point));
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
