<?php

declare(strict_types=1);

namespace Surqlize\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Search
{
    public function __construct(
        public ?string $analyzer = null,
    ) {}
}
