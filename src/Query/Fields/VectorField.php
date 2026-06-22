<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use Surqlize\Query\Compiler\Identifier;
use Surqlize\Query\Compiler\ValueFormatter;

/** Field reference for vector embedding fields backed by vector indexes. */
final class VectorField extends Field
{
    /** @param list<int|float> $vector */
    public function distanceAsc(array $vector, string $metric = 'cosine'): OrderExpression
    {
        return OrderExpression::expression($this->distanceExpression($vector, $metric), OrderDirection::Ascending);
    }

    /** @param list<int|float> $vector */
    public function distanceDesc(array $vector, string $metric = 'cosine'): OrderExpression
    {
        return OrderExpression::expression($this->distanceExpression($vector, $metric), OrderDirection::Descending);
    }

    /** @param list<int|float> $vector */
    private function distanceExpression(array $vector, string $metric): string
    {
        $metric = Identifier::alias($metric, 'vector distance metric');

        return sprintf(
            'vector::distance::%s(%s, %s)',
            $metric,
            Identifier::field($this->path(), 'vector field'),
            ValueFormatter::format($vector),
        );
    }
}
