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
use SurrealDB\SDK\Contracts\QueryExecutor;
use SurrealDB\SDK\Query\BoundQuery;
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
		$this->assertInstanceOf(\ReflectionProperty::class, $metadata->propertyReflections['id']);
		$this->assertInstanceOf(\ReflectionProperty::class, $metadata->propertyReflections['name']);
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

	public function test_model_to_array_uses_cached_reflection_and_skips_uninitialized_properties(): void
	{
		$user = new User();
		$user->name = 'beau';
		$user->age = 27;

		$this->assertSame([
			'name' => 'beau',
			'age' => 27,
			'address' => null,
		], $user->toArray());
	}

	public function test_typed_callback_error_indexes_are_normalized_without_reindexing_copy(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('index 1');

		User::select(fn (FieldSet $user) => [
			5 => $user->field('name'),
			9 => 42,
		]);
	}

	public function test_lazy_models_hydrates_models_without_materializing_model_list(): void
	{
		$executor = new PerformanceCapturingExecutor([
			['id' => 'user:beau', 'name' => 'beau', 'age' => 27],
		]);

		$models = iterator_to_array(
			User::select(['*'])
				->withExecutor($executor)
				->lazyModels(),
		);

		$this->assertCount(1, $models);
		$this->assertInstanceOf(User::class, $models[0]);
		$this->assertSame('beau', $models[0]->toArray()['name']);
	}

	public function test_mutation_first_model_hydrates_only_first_returned_row(): void
	{
		$executor = new PerformanceCapturingExecutor([
			['id' => 'user:first', 'name' => 'first', 'age' => 1],
			['id' => new \stdClass()],
		]);

		$model = User::createQuery(['name' => 'first', 'age' => 1], executor: $executor)->firstModel();

		$this->assertInstanceOf(User::class, $model);
		$this->assertSame('first', $model->name);
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

final class PerformanceCapturingExecutor implements QueryExecutor
{
	public function __construct(
		private readonly mixed $result,
	) {}

	public function query(BoundQuery $query): mixed
	{
		return $this->result;
	}
}
