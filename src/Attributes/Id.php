<?php

declare(strict_types=1);

namespace Surqlize\Attributes;

use Attribute;

/**
 * Marks the primary key property. Id kind is inferred from {@see \SurrealDB\SDK\Types\RecordId}.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Id {}
