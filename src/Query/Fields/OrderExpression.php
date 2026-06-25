<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use Surqlize\Query\Compiler\Identifier;

final readonly class OrderExpression
{
    public function __construct(
        private string $field,
        private OrderDirection $direction,
    ) {}

    public function field(): string
    {
        return $this->field;
    }

    public function direction(): OrderDirection
    {
        return $this->direction;
    }

    public function compile(): string
    {
        return Identifier::field($this->field, 'ORDER BY field') . ' ' . $this->direction->value;
    }
}
