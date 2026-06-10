<?php

declare(strict_types=1);

namespace Surqlize\Query\Support\Exception;

final class MissingTableNameAttributeException extends \InvalidArgumentException
{
    public function __construct(string $class)
    {
        parent::__construct(sprintf('Class "%s" has no #[Table] or #[Edge] attribute.', $class));
    }
}
