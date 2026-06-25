<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

use Surqlize\Query\Compiler\Identifier;

final readonly class GroupClause implements Node
{
    /** @param list<string> $fields */
    private function __construct(
        private bool $all = false,
        private array $fields = [],
    ) {}

    public static function all(): self
    {
        return new self(all: true);
    }

    /** @param list<string> $fields */
    public static function by(array $fields): self
    {
        if ($fields === []) {
            throw new \InvalidArgumentException('GROUP BY requires at least one field.');
        }

        return new self(fields: $fields);
    }

    public function compile(): string
    {
        if ($this->all) {
            return 'GROUP ALL';
        }

        $fields = [];

        foreach ($this->fields as $index => $field) {
            $fields[] = Identifier::field($field, sprintf('GROUP BY field at index %d', $index));
        }

        return 'GROUP BY ' . implode(', ', $fields);
    }
}
