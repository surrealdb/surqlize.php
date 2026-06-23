<?php

declare(strict_types=1);

namespace Surqlize\Schema;

use Surqlize\Query\Compiler\Identifier;

final class TableDefinition implements SchemaDefinition
{
    private ?string $mode = null;

    /** @var list<FieldDefinition> */
    private array $fields = [];

    /** @var list<IndexDefinition> */
    private array $indexes = [];

    /** @var list<AnalyzerDefinition> */
    private array $analyzers = [];

    public function __construct(
        private readonly string $name,
    ) {}

    public function schemafull(): self
    {
        $this->mode = 'SCHEMAFULL';

        return $this;
    }

    public function schemaless(): self
    {
        $this->mode = 'SCHEMALESS';

        return $this;
    }

    public function field(string $name): FieldDefinition
    {
        $field = new FieldDefinition($this, $name);
        $this->fields[] = $field;

        return $field;
    }

    public function index(string $name): IndexDefinition
    {
        $index = new IndexDefinition($this, $name);
        $this->indexes[] = $index;

        return $index;
    }

    public function analyzer(string $name): AnalyzerDefinition
    {
        $analyzer = new AnalyzerDefinition($name);
        $this->analyzers[] = $analyzer;

        return $analyzer;
    }

    public function addIndex(IndexDefinition $index): void
    {
        $this->indexes[] = $index;
    }

    public function tableName(): string
    {
        return Identifier::table($this->name, 'schema table');
    }

    public function definitions(): array
    {
        $definitions = [];

        foreach ($this->analyzers as $analyzer) {
            $definitions = [...$definitions, ...$analyzer->definitions()];
        }

        $definitions[] = trim(sprintf('DEFINE TABLE %s %s;', $this->tableName(), $this->mode ?? ''));

        foreach ($this->fields as $field) {
            $definitions = [...$definitions, ...$field->fieldDefinitions()];
        }

        foreach ($this->indexes as $index) {
            $definitions = [...$definitions, ...$index->definitions()];
        }

        return $definitions;
    }
}
