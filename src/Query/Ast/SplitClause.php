<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

use Surqlize\Query\Compiler\Identifier;

final readonly class SplitClause implements Node
{
    /** @param list<string> $fields */
    public function __construct(
        private array $fields,
    ) {
        if ($fields === []) {
            throw new \InvalidArgumentException('SPLIT requires at least one field.');
        }
    }

    public function compile(): string
    {
        $fields = [];

        foreach ($this->fields as $index => $field) {
            $fields[] = Identifier::field($field, sprintf('SPLIT field at index %d', $index));
        }

        return 'SPLIT ' . implode(', ', $fields);
    }
}
