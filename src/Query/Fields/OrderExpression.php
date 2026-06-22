<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use Surqlize\Query\Compiler\Identifier;

final readonly class OrderExpression
{
    public function __construct(
        private string $field,
        private OrderDirection $direction,
        private bool $expression = false,
    ) {}

    public static function expression(string $expression, OrderDirection $direction): self
    {
        return new self($expression, $direction, expression: true);
    }

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
        $field = $this->expression
            ? $this->field
            : Identifier::field($this->field, 'ORDER BY field');

        return $field . ' ' . $this->direction->value;
    }
}
