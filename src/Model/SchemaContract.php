<?php

declare(strict_types=1);

namespace Surqlize\Model;

interface SchemaContract
{
    /** @return list<string|\Surqlize\Schema\SchemaDefinition> SurrealDB schema definitions for this model. */
    public function definitions(): array;

    /** @return array<string, mixed> Optional validation rules applied before persist. */
    public function rules(): array;
}
