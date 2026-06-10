<?php

declare(strict_types=1);

namespace Surqlize\Examples\Models;

use Surqlize\Attributes\Id;
use Surqlize\Attributes\Schema;
use Surqlize\Attributes\Table;
use Surqlize\Examples\Schemas\AddressSchema;
use Surqlize\Model\Model;
use SurrealDB\SDK\Types\RecordId;

#[Table('address')]
#[Schema(AddressSchema::class)]
class Address extends Model
{
    #[Id] public RecordId $id;

    public string $street;

    public int $number;

    public string $postcode;
}
