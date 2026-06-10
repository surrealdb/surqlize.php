<?php

declare(strict_types=1);

namespace Surqlize\Benchmark;

final readonly class MemoryScenario
{
    public function __construct(
        public string $name,
        public string $description,
        private \Closure $callback,
    ) {}

    public function run(int $iterations): void
    {
        ($this->callback)($iterations);
    }
}
