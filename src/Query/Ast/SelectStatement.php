<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

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

    public function compile(): string
    {
        if ($this->valueField !== null) {
            $sql = 'SELECT VALUE ' . Identifier::field($this->valueField, 'SELECT VALUE field');
        } else {
            $sql = 'SELECT ' . $this->fields->compile();
        }

        if ($this->fromTable !== null && $this->fromTable !== '') {
            $sql .= ' FROM ' . Identifier::table($this->fromTable, 'FROM table');
        }

        if ($this->where !== null) {
            $compiledWhere = $this->where->compile();
            if ($compiledWhere !== '') {
                $sql .= ' ' . $compiledWhere;
            }
        }

        if ($this->order !== null) {
            $compiledOrder = $this->order->compile();
            if ($compiledOrder !== '') {
                $sql .= ' ' . $compiledOrder;
            }
        }

        if ($this->fetch !== null) {
            $compiledFetch = $this->fetch->compile();
            if ($compiledFetch !== '') {
                $sql .= ' ' . $compiledFetch;
            }
        }

        return $sql;
    }
}
