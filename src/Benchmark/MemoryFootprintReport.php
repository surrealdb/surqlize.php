<?php

declare(strict_types=1);

namespace Surqlize\Benchmark;

final readonly class MemoryFootprintReport implements \JsonSerializable
{
    /**
     * @param array<string, mixed> $metadata
     * @param array<string, int> $baseline
     * @param list<array<string, mixed>> $scenarios
     */
    public function __construct(
        private array $metadata,
        private array $baseline,
        private array $scenarios,
    ) {}

    /**
     * @return array{
     *     metadata: array<string, mixed>,
     *     baseline: array<string, int>,
     *     scenarios: list<array<string, mixed>>
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'metadata' => $this->metadata,
            'baseline' => $this->baseline,
            'scenarios' => $this->scenarios,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    }
}
