<?php

declare(strict_types=1);

namespace Surqlize\Schema;

use Surqlize\Query\Compiler\Identifier;

final class FieldDefinition implements SchemaDefinition
{
    private ?string $type = null;

    private ?string $default = null;

    private ?string $value = null;

    private ?string $computed = null;

    private ?string $assert = null;

    private bool $readonly = false;

    private ?string $comment = null;

    public function __construct(
        private readonly TableDefinition $table,
        private readonly string $name,
    ) {}

    public function string(): self
    {
        return $this->type('string');
    }

    public function int(): self
    {
        return $this->type('int');
    }

    public function float(): self
    {
        return $this->type('float');
    }

    public function bool(): self
    {
        return $this->type('bool');
    }

    public function datetime(): self
    {
        return $this->type('datetime');
    }

    public function array(string $itemType = 'any'): self
    {
        return $this->type('array<' . $itemType . '>');
    }

    public function record(string $table): self
    {
        return $this->type('record<' . Identifier::table($table, 'record field table') . '>');
    }

    public function geometry(): self
    {
        return $this->type('geometry');
    }

    public function vector(int $dimension, string $itemType = 'float'): self
    {
        if ($dimension < 1) {
            throw new \InvalidArgumentException('Vector dimension must be greater than zero.');
        }

        return $this->type('array<' . $itemType . '>');
    }

    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function default(string $expression): self
    {
        $this->default = $expression;

        return $this;
    }

    public function value(string $expression): self
    {
        $this->value = $expression;

        return $this;
    }

    public function computed(string $expression): self
    {
        $this->computed = $expression;

        return $this;
    }

    public function readonly(): self
    {
        $this->readonly = true;

        return $this;
    }

    public function comment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function assert(AssertSchemaBuilder|\Closure $assert): self
    {
        if ($assert instanceof \Closure) {
            $builder = new AssertSchemaBuilder();
            $result = $assert($builder);
            $assert = $result instanceof AssertSchemaBuilder ? $result : $builder;
        }

        $this->assert = $assert->compile();

        return $this;
    }

    public function unique(string $indexName): self
    {
        $this->table->addIndex((new IndexDefinition($this->table, $indexName))->fields([$this->name])->unique());

        return $this;
    }

    public function field(string $name): self
    {
        return $this->table->field($name);
    }

    public function index(string $name): IndexDefinition
    {
        return $this->table->index($name);
    }

    public function definitions(): array
    {
        return $this->table->definitions();
    }

    /** @return list<string> */
    public function fieldDefinitions(): array
    {
        $parts = [
            'DEFINE FIELD',
            Identifier::field($this->name, 'schema field'),
            'ON TABLE',
            $this->table->tableName(),
        ];

        if ($this->type !== null) {
            $parts[] = 'TYPE ' . $this->type;
        }

        if ($this->computed !== null) {
            $parts[] = 'COMPUTED ' . $this->computed;
        }

        if ($this->default !== null) {
            $parts[] = 'DEFAULT ' . $this->default;
        }

        if ($this->readonly) {
            $parts[] = 'READONLY';
        }

        if ($this->value !== null) {
            $parts[] = 'VALUE ' . $this->value;
        }

        if ($this->assert !== null) {
            $parts[] = 'ASSERT ' . $this->assert;
        }

        if ($this->comment !== null) {
            $parts[] = 'COMMENT ' . \Surqlize\Query\Compiler\ValueFormatter::format($this->comment);
        }

        return [implode(' ', $parts) . ';'];
    }
}
