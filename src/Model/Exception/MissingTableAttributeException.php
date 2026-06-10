<?php

declare(strict_types=1);

namespace Surqlize\Model\Exception;

use InvalidArgumentException;

final class MissingTableAttributeException extends InvalidArgumentException
{
    public function __construct(string $class, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Model "%s" is missing a #[Table] attribute.', $class), previous: $previous);
    }
}
