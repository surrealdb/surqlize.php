<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

use Surqlize\Query\Compiler\Identifier;

final readonly class OmitClause implements Node
{
    /** @param list<string> $fields */
    public function __construct(
        private array $fields,
    ) {}

    /** @return list<string> */
    public function fields(): array
    {
        return $this->fields;
    }

    public function compile(): string
    {
        if ($this->fields === []) {
            return '';
        }

        $fields = [];

        foreach ($this->fields as $index => $field) {
            $fields[] = Identifier::field($field, sprintf('OMIT field at index %d', $index));
        }

        return 'OMIT ' . implode(', ', $fields);
    }
}
