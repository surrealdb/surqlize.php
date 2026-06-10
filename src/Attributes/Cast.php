<?php

declare(strict_types=1);

namespace Surqlize\Attributes;

use Attribute;
use Surqlize\Model\Model;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Cast
{
    /**
     * @param class-string<Model> $class
     */
    public function __construct(
        public string $class,
    ) {}
}
