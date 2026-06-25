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

    /**
     * @param list<WherePredicate> $conditions
     */
    public static function compileBracketedConditions(array $conditions): string
    {
        if ($conditions === []) {
            return '';
        }

        return '[WHERE ' . self::compileConditionList($conditions) . ']';
    }

    /**
     * @param list<WherePredicate> $conditions
     */
    public static function compileBracketedConditionsBound(array $conditions, BoundQuery $query): string
    {
        if ($conditions === []) {
            return '';
        }

        return '[WHERE ' . self::compileConditionListBound($conditions, $query) . ']';
    }

    private function compileConditions(): string
    {
        return self::compileConditionList($this->conditions);
    }

    private function compileConditionsBound(BoundQuery $query): string
    {
        return self::compileConditionListBound($this->conditions, $query);
    }

    /**
     * @param list<WherePredicate> $conditions
     */
    private static function compileConditionList(array $conditions): string
    {
        $sql = '';

        foreach ($conditions as $condition) {
            if ($sql !== '') {
                $sql .= ' AND ';
            }

            $sql .= $condition->compile();
        }

        return $sql;
    }

    /**
     * @param list<WherePredicate> $conditions
     */
    private static function compileConditionListBound(array $conditions, BoundQuery $query): string
    {
        $sql = '';

        foreach ($conditions as $condition) {
            if ($sql !== '') {
                $sql .= ' AND ';
            }

            $sql .= $condition->compileBound($query);
        }

        return $sql;
    }
}
