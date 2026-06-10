<?php

declare(strict_types=1);

namespace Surqlize\Tests\Unit;

use Surqlize\Attributes\Table;
use Surqlize\Generator\FieldGenerationConfig;
use Surqlize\Model\Exception\MissingTableAttributeException;
use Surqlize\Model\Hydrator;
use Surqlize\Model\Model;
use Surqlize\Model\ModelMetadata;
use Surqlize\Query\Fields\FieldSet;
use Surqlize\Query\ModelQuery;
use Surqlize\Query\Support\Exception\MissingTableNameAttributeException;
use Surqlize\Tests\Fixtures\Address;
use Surqlize\Tests\Fixtures\HasAddress;
use Surqlize\Tests\Fixtures\User;
use Surqlize\Tests\TestCase;
use SurrealDB\SDK\Contracts\QueryExecutor;
use SurrealDB\SDK\Query\BoundQuery;
use SurrealDB\SDK\Types\RecordId;

final class ErrorHandlingTest extends TestCase
{
    public function test_missing_table_attribute_preserves_previous_exception(): void
    {
        try {
            ModelMetadata::for(MissingTableModel::class);
            $this->fail('Expected missing table attribute exception.');
        } catch (MissingTableAttributeException $exception) {
            $this->assertStringContainsString(MissingTableModel::class, $exception->getMessage());
            $this->assertInstanceOf(MissingTableNameAttributeException::class, $exception->getPrevious());
        }
    }

    public function test_invalid_table_identifier_is_not_reported_as_missing_table(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Table name from #[Table]');

        ModelMetadata::for(ErrorUnsafeTableNameModel::class);
    }

    public function test_dynamic_field_resolution_does_not_swallow_model_metadata_errors(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Table name from #[Table]');

        (new FieldSet(ErrorUnsafeTableNameModel::class))->field('name');
    }

    public function test_identifier_errors_include_query_context(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SELECT field at index 0');

        User::select(['name; DELETE user'])->compile();
    }

    public function test_typed_callback_errors_include_operation_and_model_context(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('select() typed callback for model "' . User::class . '"');

        User::select(fn () => [42]);
    }

    public function test_collect_rejects_non_array_executor_results(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected query executor to return a list of rows');
        $this->expectExceptionMessage('SELECT name FROM user');

        User::select(['name'])
            ->withExecutor(new MalformedResultExecutor('not rows'))
            ->collect();
    }

    public function test_collect_rejects_non_array_rows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected query row at index 0 to be an array');

        User::select(['name'])
            ->withExecutor(new MalformedResultExecutor(['not a row']))
            ->collect();
    }

    public function test_edge_query_builder_order_error_includes_call_pattern(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Call select([...]) or selectValue(...) first');

        (new HasAddress())->in()->compile();
    }

    public function test_relate_missing_edge_error_includes_next_step(): void
    {
        $user = new User();
        $user->id = new RecordId('user', 'beau');

        $address = new Address();
        $address->id = new RecordId('address', 'home');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Call edge(YourEdge::class)');

        User::relate($user)->with($address);
    }

    public function test_hydrator_errors_include_model_and_property_context(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot hydrate model "' . User::class . '" property "id"');

        (new Hydrator())->hydrate(User::class, ['id' => new \stdClass()]);
    }

    public function test_field_generation_config_validates_models_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('config "models" must be a list');

        (new \ReflectionMethod(FieldGenerationConfig::class, 'fromArray'))->invoke(null, [
            'models' => 'not a list',
            'fields_namespace' => 'Generated\\Fields',
            'fields_path' => __DIR__,
        ]);
    }
}

final class MalformedResultExecutor implements QueryExecutor
{
    public function __construct(
        private readonly mixed $result,
    ) {}

    public function query(BoundQuery $query): mixed
    {
        return $this->result;
    }
}

final class MissingTableModel extends Model
{
    public string $name;
}

#[Table('unsafe; table')]
final class ErrorUnsafeTableNameModel extends Model
{
    public string $name;
}
