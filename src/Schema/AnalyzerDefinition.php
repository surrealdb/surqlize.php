<?php

declare(strict_types=1);

namespace Surqlize\Schema;

use Surqlize\Query\Compiler\Identifier;

final class AnalyzerDefinition implements SchemaDefinition
{
    /** @var list<string> */
    private array $tokenizers = [];

    /** @var list<string> */
    private array $filters = [];

    public function __construct(
        private readonly string $name,
    ) {}

    /** @param list<string> $tokenizers */
    public function tokenizers(array $tokenizers): self
    {
        $this->tokenizers = $tokenizers;

        return $this;
    }

    /** @param list<string> $filters */
    public function filters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    public function definitions(): array
    {
        $parts = ['DEFINE ANALYZER', Identifier::alias($this->name, 'analyzer name')];

        if ($this->tokenizers !== []) {
            $parts[] = 'TOKENIZERS ' . $this->compileNames($this->tokenizers, 'analyzer tokenizer');
        }

        if ($this->filters !== []) {
            $parts[] = 'FILTERS ' . $this->compileNames($this->filters, 'analyzer filter');
        }

        return [implode(' ', $parts) . ';'];
    }

    /** @param list<string> $names */
    private function compileNames(array $names, string $context): string
    {
        $compiled = [];

        foreach ($names as $index => $name) {
            $compiled[] = Identifier::alias($name, sprintf('%s at index %d', $context, $index));
        }

        return implode(', ', $compiled);
    }
}
