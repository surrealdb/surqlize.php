<?php

declare(strict_types=1);

namespace Surqlize\Examples\Schemas;

use Surqlize\Model\SchemaContract;

final class HasAddressSchema implements SchemaContract
{
    public function definitions(): array
    {
        return [
            'DEFINE TABLE has_address TYPE RELATION FROM user TO address;',
        ];
    }

    public function rules(): array
    {
        return [];
    }
}
