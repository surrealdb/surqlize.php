<?php

declare(strict_types=1);

namespace Surqlize\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Edge
{
    /**
     * @param class-string $in
     * @param class-string $out
     */
    public function __construct(
        public string $name,
        public string $in,
        public string $out,
    ) {}
}
