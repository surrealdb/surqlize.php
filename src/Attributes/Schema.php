<?php

declare(strict_types=1);

namespace Surqlize\Attributes;

use Attribute;
use Surqlize\Model\SchemaContract;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Schema
{
    /**
     * @param class-string<SchemaContract> $class
     */
    public function __construct(
        public string $class,
    ) {}
}
