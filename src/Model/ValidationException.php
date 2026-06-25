<?php

declare(strict_types=1);

namespace Surqlize\Model;

final class ValidationException extends \InvalidArgumentException
{
    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(
        public readonly string $modelClass,
        public readonly array $errors,
    ) {
        parent::__construct(sprintf('Validation failed for model "%s".', $modelClass));
    }
}
