<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

use Surqlize\Query\Compiler\Identifier;

/**
 * AST node for graph traversal segments in SELECT field lists.
 *
 * Compiles chains such as `->has_address->address[WHERE postcode INCLUDES '24'] AS address`.
 * Built by the Edge package's GraphSelectField; v1 supports simple field WHERE in brackets.
 */
class GraphTraversal implements Node
{
    /** @param list<WhereCondition> $where */
    public function __construct(
        public readonly GraphDirection $direction,
        public readonly string $segment,
        public readonly array $where = [],
        public readonly ?GraphTraversal $next = null,
        public readonly ?string $alias = null,
    ) {}

    public function compile(): string
    {
        $sql = $this->direction->arrow() . Identifier::table($this->segment, 'graph traversal segment');

        if ($this->where !== []) {
            $clause = new WhereClause($this->where);
            $sql .= $clause->compileBracketed();
        }

        if ($this->next !== null) {
            $sql .= $this->next->compile();
        }

        if ($this->alias !== null) {
            $sql .= ' AS ' . Identifier::alias($this->alias, 'graph alias');
        }

        return $sql;
    }
}
