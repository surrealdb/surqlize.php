<?php

declare(strict_types=1);

namespace Surqlize\Tests\Unit;

use Surqlize\Model\Hydrator;
use Surqlize\Model\ModelMetadata;
use Surqlize\Query\Fields\FieldSet;
use Surqlize\Query\Fields\FieldSetRegistry;
use Surqlize\Query\Fields\StringField;
use Surqlize\Query\Support\EdgeEndpointResolver;
use Surqlize\Query\EdgeDirection;
use Surqlize\Tests\Fixtures\Address;
use Surqlize\Tests\Fixtures\Fields\UserFields;
use Surqlize\Tests\Fixtures\HasAddress;
use Surqlize\Tests\Fixtures\User;
use Surqlize\Tests\TestCase;
use SurrealDB\SDK\Types\RecordId;

final class PerformanceOptimizationTest extends TestCase
{
	protected function tearDown(): void
	{
		ModelMetadata::clear();

		parent::tearDown();
	}

	public function test_model_metadata_exposes_property_lookup_and_types(): void
	{
		$metadata = ModelMetadata::for(User::class);

		$this->assertSame(['id', 'name', 'age', 'address'], $metadata->properties);
		$this->assertArrayHasKey('name', $metadata->propertyLookup);
		$this->assertArrayHasKey('address', $metadata->propertyLookup);
		$this->assertSame('string', $metadata->propertyTypes['name']);
		$this->assertSame('int', $metadata->propertyTypes['age']);
		$this->assertSame(Address::class, $metadata->propertyTypes['address']);
	}

	public function test_hydrator_ignores_unknown_keys_with_property_lookup(): void
	{
		$model = (new Hydrator())->hydrate(User::class, [
			'id' => 'user:beau',
			'name' => 'beau',
			'age' => 27,
			'unknown' => 'ignored',
		]);

		$this->assertInstanceOf(User::class, $model);
		$this->assertInstanceOf(RecordId::class, $model->id);
		$this->assertSame('beau', $model->name);
		$this->assertSame(27, $model->age);
		$this->assertFalse(property_exists($model, 'unknown'));
	}

	public function test_dynamic_field_types_use_cached_metadata(): void
	{
		$field = (new FieldSet(User::class))->field('name');

		$this->assertInstanceOf(StringField::class, $field);
	}

	public function test_field_set_registry_clear_resets_registered_state_without_reusing_instances(): void
	{
		FieldSetRegistry::clear();
		FieldSetRegistry::register(User::class, UserFields::class);

		$first = FieldSetRegistry::resolve(User::class);
		$second = FieldSetRegistry::resolve(User::class);

		$this->assertInstanceOf(UserFields::class, $first);
		$this->assertInstanceOf(UserFields::class, $second);
		$this->assertNotSame($first, $second);

		FieldSetRegistry::clear();

		$this->assertSame([], FieldSetRegistry::all());
		$this->assertInstanceOf(UserFields::class, FieldSetRegistry::resolve(User::class));
	}

	public function test_edge_endpoint_resolver_returns_cached_metadata_values(): void
	{
		$this->assertSame(User::class, EdgeEndpointResolver::endpointClass(HasAddress::class, EdgeDirection::In));
		$this->assertSame(Address::class, EdgeEndpointResolver::endpointClass(HasAddress::class, EdgeDirection::Out));
		$this->assertSame('user', EdgeEndpointResolver::endpointTable(HasAddress::class, EdgeDirection::In));
		$this->assertSame('address', EdgeEndpointResolver::endpointTable(HasAddress::class, EdgeDirection::Out));
		$this->assertSame('has_address', EdgeEndpointResolver::edgeTableName(HasAddress::class));
	}
}
