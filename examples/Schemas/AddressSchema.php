<?php

declare(strict_types=1);

namespace Surqlize\Examples\Schemas;

use Surqlize\Model\SchemaContract;

final class AddressSchema implements SchemaContract
{
    public function definitions(): array
    {
        return [
            'DEFINE TABLE address SCHEMAFULL;',
            'DEFINE FIELD street ON address TYPE string;',
            'DEFINE FIELD number ON address TYPE int;',
            'DEFINE FIELD postcode ON address TYPE string;',
        ];
    }

    public function rules(): array
    {
        return [];
    }
}
