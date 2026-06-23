<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use Surqlize\Query\Ast\ExpressionProjection;
use Surqlize\Query\Ast\WhereCondition;

/** Field reference for vector embedding fields backed by vector indexes. */
final class VectorField extends Field
{
    /** @param list<int|float> $vector */
    public function nearest(array $vector, int $k, ?int $effort = null): WhereCondition
    {
        if ($k < 1) {
            throw new \InvalidArgumentException('Vector nearest k must be greater than zero.');
        }

        if ($effort !== null && $effort < 1) {
            throw new \InvalidArgumentException('Vector nearest effort must be greater than zero.');
        }

        $operator = $effort === null ? sprintf('<|%d|>', $k) : sprintf('<|%d, %d|>', $k, $effort);

        return $this->condition($operator, $vector);
    }

    public function knnDistance(): ExpressionProjection
    {
        return new ExpressionProjection('vector::distance::knn()');
    }
}
