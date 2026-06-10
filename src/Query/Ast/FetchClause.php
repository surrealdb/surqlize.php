<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

use Surqlize\Query\Compiler\Identifier;

final class FetchClause implements Node
{
    /** @var list<string> */
    private array $fields;

    /** @param list<string>|string $fields */
    public function __construct(array|string $fields = [])
    {
        $this->fields = is_array($fields) ? $fields : [$fields];
    }

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
            $fields[] = Identifier::field($field, sprintf('FETCH field at index %d', $index));
        }

        return 'FETCH ' . implode(', ', $fields);
    }
}
