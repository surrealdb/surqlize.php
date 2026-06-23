<?php

declare(strict_types=1);

namespace Surqlize\Schema;

use Surqlize\Query\Compiler\Identifier;
use Surqlize\Query\Compiler\ValueFormatter;

final class AssertSchemaBuilder
{
    /** @var list<string> */
    private array $expressions = [];

    public function required(): self
    {
        $this->expressions[] = '$value != NONE';

        return $this;
    }

    public function email(): self
    {
        $this->expressions[] = 'string::is::email($value)';

        return $this;
    }

    public function minLength(int $length): self
    {
        $this->expressions[] = 'string::len($value) >= ' . $this->positive($length, 'Minimum length');

        return $this;
    }

    public function maxLength(int $length): self
    {
        $this->expressions[] = 'string::len($value) <= ' . $this->positive($length, 'Maximum length');

        return $this;
    }

    public function between(int|float $min, int|float $max): self
    {
        $this->expressions[] = sprintf('$value >= %s AND $value <= %s', (string) $min, (string) $max);

        return $this;
    }

    public function greaterThan(int|float $value): self
    {
        $this->expressions[] = '$value > ' . $value;

        return $this;
    }

    public function lessThan(int|float $value): self
    {
        $this->expressions[] = '$value < ' . $value;

        return $this;
    }

    public function matchesRegex(string $pattern): self
    {
        $this->expressions[] = '$value ~ ' . ValueFormatter::format($pattern);

        return $this;
    }

    public function isRecord(string $table): self
    {
        $this->expressions[] = 'record::tb($value) = ' . ValueFormatter::format(Identifier::table($table, 'assert record table'));

        return $this;
    }

    public function customExpression(string $expression): self
    {
        $this->expressions[] = $expression;

        return $this;
    }

    public function compile(): string
    {
        if ($this->expressions === []) {
            throw new \LogicException('Schema assert builder must contain at least one assertion.');
        }

        return implode(' AND ', $this->expressions);
    }

    private function positive(int $value, string $label): int
    {
        if ($value < 1) {
            throw new \InvalidArgumentException($label . ' must be greater than zero.');
        }

        return $value;
    }
}
