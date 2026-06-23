<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

use SurrealDB\SDK\Query\BoundQuery;

final class WhereClause implements Node
{
    /** @var list<WherePredicate> */
    private array $conditions = [];

    /** @param list<WherePredicate> $conditions */
    public function __construct(array $conditions = [])
    {
        $this->conditions = $conditions;
    }

    public function add(WherePredicate $condition): self
    {
        $this->conditions[] = $condition;

        return $this;
    }

    /** @return list<WherePredicate> */
    public function conditions(): array
    {
        return $this->conditions;
    }

    public function compile(): string
    {
        if ($this->conditions === []) {
            return '';
        }

        return 'WHERE ' . $this->compileConditions();
    }

    public function compileBound(BoundQuery $query): string
    {
        if ($this->conditions === []) {
            return '';
        }

        return 'WHERE ' . $this->compileConditionsBound($query);
    }

    /** For graph bracket syntax: `[WHERE field op value]`. */
    public function compileBracketed(): string
    {
        if ($this->conditions === []) {
            return '';
        }

        return '[WHERE ' . $this->compileConditions() . ']';
    }

    public function compileBracketedBound(BoundQuery $query): string
    {
        if ($this->conditions === []) {
            return '';
        }

        return '[WHERE ' . $this->compileConditionsBound($query) . ']';
    }

    private function compileConditions(): string
    {
		$compiled = [];

		foreach ($this->conditions as $condition) {
			$compiled[] = $condition->compile();
		}

		return implode(' AND ', $compiled);
    }

    private function compileConditionsBound(BoundQuery $query): string
    {
		$compiled = [];

		foreach ($this->conditions as $condition) {
			$compiled[] = $condition->compileBound($query);
		}

		return implode(' AND ', $compiled);
    }
}
