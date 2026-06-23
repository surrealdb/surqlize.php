<?php

declare(strict_types=1);

namespace Surqlize\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Vector
{
    public function __construct(
        public int $dimension,
        public string $distance = 'cosine',
    ) {}
}
