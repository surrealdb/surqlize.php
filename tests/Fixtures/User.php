<?php

declare(strict_types=1);

namespace Surqlize\Tests\Fixtures;

use Surqlize\Attributes\Cast;
use Surqlize\Attributes\Id;
use Surqlize\Attributes\Schema;
use Surqlize\Attributes\Table;
use Surqlize\Model\Model;
use Surqlize\Tests\Fixtures\Fields\UserFieldTyping;
use SurrealDB\SDK\Types\RecordId;

#[Table('user')]
#[Schema(UserSchema::class)]
class User extends Model
{
    use UserFieldTyping;

    #[Id] public RecordId $id;

    public string $name;

    public int $age;

    #[Cast(Address::class)] public ?Address $address = null;
}
