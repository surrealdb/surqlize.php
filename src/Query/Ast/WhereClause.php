<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

final class WhereClause implements Node
{
    /** @var list<WhereCondition> */
    private array $conditions = [];

    /** @param list<WhereCondition> $conditions */
    public function __construct(array $conditions = [])
    {
        $this->conditions = $conditions;
    }

    public function add(WhereCondition $condition): self
    {
        $this->conditions[] = $condition;

        return $this;
    }

    /** @return list<WhereCondition> */
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

    /** For graph bracket syntax: `[WHERE field op value]`. */
    public function compileBracketed(): string
    {
        if ($this->conditions === []) {
            return '';
        }

        return '[WHERE ' . $this->compileConditions() . ']';
    }

    private function compileConditions(): string
    {
		$compiled = [];

		foreach ($this->conditions as $condition) {
			$compiled[] = $condition->compile();
		}

		return implode(' AND ', $compiled);
    }
}
