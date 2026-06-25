<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

use SurrealDB\SDK\Query\BoundQuery;
use Surqlize\Query\Compiler\Identifier;

final class SelectStatement implements Node
{
    public function __construct(
        private FieldSelection $fields,
        private ?string $fromTable,
        private ?WhereClause $where = null,
        private ?FetchClause $fetch = null,
        private ?string $valueField = null,
        private ?OrderClause $order = null,
        private ?int $limit = null,
        private ?int $start = null,
        private ?OmitClause $omit = null,
        private ?WithIndexClause $withIndex = null,
        private ?SplitClause $split = null,
        private ?GroupClause $group = null,
        private ?ExplainClause $explain = null,
        private ?TimeoutClause $timeout = null,
        private bool $tempfiles = false,
    ) {}

    public function fields(): FieldSelection
    {
        return $this->fields;
    }

    public function fromTable(): ?string
    {
        return $this->fromTable;
    }

    public function where(): ?WhereClause
    {
        return $this->where;
    }

    public function fetch(): ?FetchClause
    {
        return $this->fetch;
    }

    public function valueField(): ?string
    {
        return $this->valueField;
    }

    public function order(): ?OrderClause
    {
        return $this->order;
    }

    public function limit(): ?int
    {
        return $this->limit;
    }

    public function start(): ?int
    {
        return $this->start;
    }

    public function explain(): ?ExplainClause
    {
        return $this->explain;
    }

    public function isSelectValue(): bool
    {
        return $this->valueField !== null;
    }

    public function withFields(FieldSelection $fields): self
    {
        $clone = clone $this;
        $clone->fields = $fields;
        $clone->valueField = null;

        return $clone;
    }

    public function withValueField(string $field): self
    {
        $clone = clone $this;
        $clone->valueField = $field;
        $clone->fields = new FieldSelection([$field]);

        return $clone;
    }

    public function withFromTable(?string $fromTable): self
    {
        $clone = clone $this;
        $clone->fromTable = $fromTable;

        return $clone;
    }

    public function withWhere(?WhereClause $where): self
    {
        $clone = clone $this;
        $clone->where = $where;

        return $clone;
    }

    public function withFetch(?FetchClause $fetch): self
    {
        $clone = clone $this;
        $clone->fetch = $fetch;

        return $clone;
    }

    public function withOrder(?OrderClause $order): self
    {
        $clone = clone $this;
        $clone->order = $order;

        return $clone;
    }

    public function withLimit(?int $limit): self
    {
        if ($limit !== null && $limit < 1) {
            throw new \InvalidArgumentException('SELECT LIMIT must be greater than zero.');
        }

        $clone = clone $this;
        $clone->limit = $limit;

        return $clone;
    }

    public function withStart(?int $start): self
    {
        if ($start !== null && $start < 0) {
            throw new \InvalidArgumentException('SELECT START must be zero or greater.');
        }

        $clone = clone $this;
        $clone->start = $start;

        return $clone;
    }

    public function withOmit(?OmitClause $omit): self
    {
        $clone = clone $this;
        $clone->omit = $omit;

        return $clone;
    }

    public function withIndex(?WithIndexClause $withIndex): self
    {
        $clone = clone $this;
        $clone->withIndex = $withIndex;

        return $clone;
    }

    public function withSplit(?SplitClause $split): self
    {
        if ($split !== null && $this->group !== null) {
            throw new \LogicException('SPLIT and GROUP cannot be used in the same SELECT statement.');
        }

        $clone = clone $this;
        $clone->split = $split;

        return $clone;
    }

    public function withGroup(?GroupClause $group): self
    {
        if ($group !== null && $this->split !== null) {
            throw new \LogicException('GROUP and SPLIT cannot be used in the same SELECT statement.');
        }

        $clone = clone $this;
        $clone->group = $group;

        return $clone;
    }

    public function withExplain(?ExplainClause $explain): self
    {
        $clone = clone $this;
        $clone->explain = $explain;

        return $clone;
    }

    public function withTimeout(?TimeoutClause $timeout): self
    {
        $clone = clone $this;
        $clone->timeout = $timeout;

        return $clone;
    }

    public function withTempfiles(bool $tempfiles = true): self
    {
        $clone = clone $this;
        $clone->tempfiles = $tempfiles;

        return $clone;
    }

    public function compile(): string
    {
        if ($this->valueField !== null) {
            $sql = 'SELECT VALUE ' . Identifier::field($this->valueField, 'SELECT VALUE field');
        } else {
            $sql = 'SELECT ' . $this->fields->compile();
        }

        if ($this->omit !== null) {
            $compiledOmit = $this->omit->compile();
            if ($compiledOmit !== '') {
                $sql .= ' ' . $compiledOmit;
            }
        }

        if ($this->fromTable !== null && $this->fromTable !== '') {
            $sql .= ' FROM ' . Identifier::table($this->fromTable, 'FROM table');
        }

        if ($this->withIndex !== null) {
            $sql .= ' ' . $this->withIndex->compile();
        }

        if ($this->where !== null) {
            $compiledWhere = $this->where->compile();
            if ($compiledWhere !== '') {
                $sql .= ' ' . $compiledWhere;
            }
        }

        if ($this->split !== null) {
            $sql .= ' ' . $this->split->compile();
        }

        if ($this->group !== null) {
            $sql .= ' ' . $this->group->compile();
        }

        if ($this->order !== null) {
            $compiledOrder = $this->order->compile();
            if ($compiledOrder !== '') {
                $sql .= ' ' . $compiledOrder;
            }
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        if ($this->start !== null) {
            $sql .= ' START ' . $this->start;
        }

        if ($this->fetch !== null) {
            $compiledFetch = $this->fetch->compile();
            if ($compiledFetch !== '') {
                $sql .= ' ' . $compiledFetch;
            }
        }

        if ($this->timeout !== null) {
            $sql .= ' ' . $this->timeout->compile();
        }

        if ($this->tempfiles) {
            $sql .= ' TEMPFILES';
        }

        if ($this->explain !== null) {
            $sql .= ' ' . $this->explain->compile();
        }

        return $sql;
    }

    public function toBoundQuery(): BoundQuery
    {
        $query = new BoundQuery();

        if ($this->valueField !== null) {
            $query->append('SELECT VALUE ' . Identifier::field($this->valueField, 'SELECT VALUE field'));
        } else {
            $query->append('SELECT ' . $this->fields->compileBound($query));
        }

        if ($this->omit !== null) {
            $compiledOmit = $this->omit->compile();
            if ($compiledOmit !== '') {
                $query->append(' ' . $compiledOmit);
            }
        }

        if ($this->fromTable !== null && $this->fromTable !== '') {
            $query->append(' FROM ' . Identifier::table($this->fromTable, 'FROM table'));
        }

        if ($this->withIndex !== null) {
            $query->append(' ' . $this->withIndex->compile());
        }

        if ($this->where !== null) {
            $compiledWhere = $this->where->compileBound($query);
            if ($compiledWhere !== '') {
                $query->append(' ' . $compiledWhere);
            }
        }

        if ($this->split !== null) {
            $query->append(' ' . $this->split->compile());
        }

        if ($this->group !== null) {
            $query->append(' ' . $this->group->compile());
        }

        if ($this->order !== null) {
            $compiledOrder = $this->order->compile();
            if ($compiledOrder !== '') {
                $query->append(' ' . $compiledOrder);
            }
        }

        if ($this->limit !== null) {
            $query->append(' LIMIT ' . $this->limit);
        }

        if ($this->start !== null) {
            $query->append(' START ' . $this->start);
        }

        if ($this->fetch !== null) {
            $compiledFetch = $this->fetch->compile();
            if ($compiledFetch !== '') {
                $query->append(' ' . $compiledFetch);
            }
        }

        if ($this->timeout !== null) {
            $query->append(' ' . $this->timeout->compile());
        }

        if ($this->tempfiles) {
            $query->append(' TEMPFILES');
        }

        if ($this->explain !== null) {
            $query->append(' ' . $this->explain->compile());
        }

        return $query;
    }
}
