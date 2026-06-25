<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

use SurrealDB\SDK\Query\BoundQuery;
use Surqlize\Query\Compiler\Identifier;

/**
 * AST node for graph traversal segments in SELECT field lists.
 *
 * Compiles chains such as `->has_address->address[WHERE postcode INCLUDES '24'] AS address`.
 * Built by the Edge package's GraphSelectField; v1 supports simple field WHERE in brackets.
 */
class GraphTraversal implements Node
{
    /** @param list<WherePredicate> $where */
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
            $sql .= WhereClause::compileBracketedConditions($this->where);
        }

        if ($this->next !== null) {
            $sql .= $this->next->compile();
        }

        if ($this->alias !== null) {
            $sql .= ' AS ' . Identifier::alias($this->alias, 'graph alias');
        }

        return $sql;
    }

    public function compileBound(BoundQuery $query): string
    {
        $sql = $this->direction->arrow() . Identifier::table($this->segment, 'graph traversal segment');

        if ($this->where !== []) {
            $sql .= WhereClause::compileBracketedConditionsBound($this->where, $query);
        }

        if ($this->next !== null) {
            $sql .= $this->next->compileBound($query);
        }

        if ($this->alias !== null) {
            $sql .= ' AS ' . Identifier::alias($this->alias, 'graph alias');
        }

        return $sql;
    }
}
