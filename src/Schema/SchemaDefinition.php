<?php

declare(strict_types=1);

namespace Surqlize\Schema;

interface SchemaDefinition
{
    /** @return list<string> */
    public function definitions(): array;
}
