<?php

declare(strict_types=1);

namespace Surqlize\Tests\Fixtures;

use Surqlize\Attributes\Edge;
use Surqlize\Attributes\Schema;
use Surqlize\Edge\Edge as EdgeModel;
use Surqlize\Tests\Fixtures\Fields\HasAddressFieldTyping;

#[Edge("has_address", in: User::class, out: Address::class)]
#[Schema(HasAddressSchema::class)]
class HasAddress extends EdgeModel
{
	use HasAddressFieldTyping;

	/**
	 * @return array<int, mixed>
	 */
	public function hasUserOverAge(int $age): array
	{
		return $this->in()
			->select(["name"])
			->where("age", ">", 27)
			->collect();
	}

	/**
	 * @return array<int, mixed>
	 */
	public function hasUserUnderAge(int $age): array
	{
		return $this->in()
			->selectValue("name")
			->where("age", ">", 27)
			->collect();
	}
}
