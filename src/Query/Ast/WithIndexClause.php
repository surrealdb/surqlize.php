<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

use Surqlize\Query\Compiler\Identifier;

final readonly class WithIndexClause implements Node
{
    /** @param list<string> $indexes */
    private function __construct(
        private bool $noIndex,
        private array $indexes = [],
    ) {}

    public static function noIndex(): self
    {
        return new self(true);
    }

    /** @param list<string> $indexes */
    public static function indexes(array $indexes): self
    {
        if ($indexes === []) {
            throw new \InvalidArgumentException('WITH INDEX requires at least one index name.');
        }

        return new self(false, $indexes);
    }

    public function compile(): string
    {
        if ($this->noIndex) {
            return 'WITH NOINDEX';
        }

        $indexes = [];

        foreach ($this->indexes as $index => $name) {
            $indexes[] = Identifier::alias($name, sprintf('WITH INDEX name at index %d', $index));
        }

        return 'WITH INDEX ' . implode(', ', $indexes);
    }
}
