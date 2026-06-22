<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

use SurrealDB\SDK\Query\BoundQuery;
use Surqlize\Query\Compiler\ValueFormatter;
use Surqlize\Query\Compiler\Identifier;
use Surqlize\Query\Operator;

final readonly class WhereCondition
{
    public function __construct(
        public string $field,
        public Operator|string $operator,
        public mixed $value,
    ) {}

    public function compile(): string
    {
        $operator = Identifier::operator($this->operator, 'WHERE operator');

        return sprintf(
            '%s %s %s',
            Identifier::field($this->field, 'WHERE field'),
            $operator,
            ValueFormatter::format($this->value, $this->operator),
        );
    }

    public function compileBound(BoundQuery $query): string
    {
        $operator = Identifier::operator($this->operator, 'WHERE operator');

        return sprintf(
            '%s %s %s',
            Identifier::field($this->field, 'WHERE field'),
            $operator,
            $query->bind($this->value),
        );
    }
}
