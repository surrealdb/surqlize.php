<?php

declare(strict_types=1);

namespace Surqlize\Tests\Fixtures;

use Surqlize\Attributes\Id;
use Surqlize\Attributes\Schema;
use Surqlize\Attributes\Table;
use Surqlize\Model\Model;
use Surqlize\Tests\Fixtures\Fields\AddressFieldTyping;
use SurrealDB\SDK\Types\RecordId;

#[Table('address')]
#[Schema(AddressSchema::class)]
class Address extends Model
{
    use AddressFieldTyping;

    /** @var RecordId<'address'> */
    #[Id] public RecordId $id;

    public string $street;

    public int $number;

    public string $postcode;
}
