<?php

declare(strict_types=1);

namespace Surqlize\Examples\Models;

use Surqlize\Attributes\Cast;
use Surqlize\Attributes\Id;
use Surqlize\Attributes\Schema;
use Surqlize\Attributes\Table;
use Surqlize\Examples\Schemas\UserSchema;
use Surqlize\Model\Model;
use SurrealDB\SDK\Types\RecordId;

#[Table('user')]
#[Schema(UserSchema::class)]
class User extends Model
{
    #[Id] public RecordId $id;

    public string $name;

    public int $age;

    #[Cast(Address::class)] public ?Address $address = null;
}
