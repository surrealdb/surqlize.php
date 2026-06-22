<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

use SurrealDB\SDK\Query\BoundQuery;
use Surqlize\Query\Compiler\Identifier;

final class FieldSelection implements Node
{
    /** @var list<string|GraphTraversal> */
    private array $fields;

    /** @param list<string|GraphTraversal> $fields */
    public function __construct(array $fields = ['*'])
    {
        $this->fields = $fields;
    }

    /** @return list<string|GraphTraversal> */
    public function fields(): array
    {
        return $this->fields;
    }

    public function compile(): string
    {
        if ($this->fields === []) {
            return '*';
        }

        $compiled = [];

        foreach ($this->fields as $index => $field) {
            $compiled[] = is_string($field)
                ? Identifier::selection($field, sprintf('SELECT field at index %d', $index))
                : $field->compile();
        }

        return implode(', ', $compiled);
    }

    public function compileBound(BoundQuery $query): string
    {
        if ($this->fields === []) {
            return '*';
        }

        $compiled = [];

        foreach ($this->fields as $index => $field) {
            $compiled[] = is_string($field)
                ? Identifier::selection($field, sprintf('SELECT field at index %d', $index))
                : $field->compileBound($query);
        }

        return implode(', ', $compiled);
    }
}
