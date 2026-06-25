<?php

declare(strict_types=1);

namespace Surqlize\Model\Exception;

final class ModelNotFoundException extends \RuntimeException
{
    public function __construct(string $modelClass, mixed $id = null)
    {
        parent::__construct($id === null
            ? sprintf('Model "%s" was not found.', $modelClass)
            : sprintf('Model "%s" with id "%s" was not found.', $modelClass, (string) $id));
    }
}
