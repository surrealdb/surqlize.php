<?php

declare(strict_types=1);

namespace Surqlize\Examples\Models;

use Surqlize\Attributes\Edge;
use Surqlize\Attributes\Schema;
use Surqlize\Edge\Edge as EdgeModel;
use Surqlize\Examples\Schemas\HasAddressSchema;

#[Edge('has_address', in: User::class, out: Address::class)]
#[Schema(HasAddressSchema::class)]
class HasAddress extends EdgeModel
{
}