<?php

declare(strict_types=1);

namespace Surqlize\Query\Ast;

final readonly class TimeoutClause implements Node
{
    public function __construct(
        private int $amount,
        private string $unit = 's',
    ) {
        if ($amount < 1) {
            throw new \InvalidArgumentException('TIMEOUT amount must be greater than zero.');
        }

        if (! in_array($unit, ['ns', 'us', 'ms', 's', 'm', 'h', 'w'], true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported TIMEOUT unit "%s".', $unit));
        }
    }

    public function compile(): string
    {
        return 'TIMEOUT ' . $this->amount . $this->unit;
    }
}
