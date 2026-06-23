<?php

declare(strict_types=1);

namespace Surqlize\Schema;

use Surqlize\Query\Compiler\Identifier;

final class IndexDefinition implements SchemaDefinition
{
    /** @var list<string> */
    private array $fields = [];

    private ?string $special = null;

    public function __construct(
        private readonly TableDefinition $table,
        private readonly string $name,
    ) {}

    /** @param list<string> $fields */
    public function fields(array $fields): self
    {
        if ($fields === []) {
            throw new \InvalidArgumentException('Index requires at least one field.');
        }

        $this->fields = $fields;

        return $this;
    }

    public function unique(): self
    {
        $this->special = 'UNIQUE';

        return $this;
    }

    public function fullText(string $analyzer, bool $bm25 = true, bool $highlights = false): self
    {
        $this->special = 'FULLTEXT ANALYZER ' . Identifier::alias($analyzer, 'full-text analyzer');

        if ($bm25) {
            $this->special .= ' BM25';
        }

        if ($highlights) {
            $this->special .= ' HIGHLIGHTS';
        }

        return $this;
    }

    public function hnsw(int $dimension, string $distance = 'COSINE', ?string $type = null): self
    {
        if ($dimension < 1) {
            throw new \InvalidArgumentException('Vector index dimension must be greater than zero.');
        }

        $this->special = sprintf('HNSW DIMENSION %d DIST %s', $dimension, strtoupper(Identifier::alias($distance, 'vector distance')));

        if ($type !== null) {
            $this->special .= ' TYPE ' . strtoupper(Identifier::alias($type, 'vector storage type'));
        }

        return $this;
    }

    public function definitions(): array
    {
        if ($this->fields === []) {
            throw new \LogicException(sprintf('Index "%s" must define fields before it can be compiled.', $this->name));
        }

        $fields = [];

        foreach ($this->fields as $index => $field) {
            $fields[] = Identifier::field($field, sprintf('index field at index %d', $index));
        }

        $sql = sprintf(
            'DEFINE INDEX %s ON TABLE %s FIELDS %s',
            Identifier::alias($this->name, 'index name'),
            $this->table->tableName(),
            implode(', ', $fields),
        );

        if ($this->special !== null) {
            $sql .= ' ' . $this->special;
        }

        return [$sql . ';'];
    }
}
