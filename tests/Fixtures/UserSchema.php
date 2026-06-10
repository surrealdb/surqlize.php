<?php

declare(strict_types=1);

namespace Surqlize\Tests\Fixtures;

use Surqlize\Model\SchemaContract;

final class UserSchema implements SchemaContract
{
    public function definitions(): array
    {
        return [
            'DEFINE TABLE user SCHEMAFULL;',
            'DEFINE FIELD name ON user TYPE string;',
            'DEFINE FIELD age ON user TYPE int;',
        ];
    }

    public function rules(): array
    {
        return [];
    }
}
