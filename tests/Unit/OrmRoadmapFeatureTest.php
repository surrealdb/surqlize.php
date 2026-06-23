<?php

declare(strict_types=1);

namespace Surqlize\Tests\Unit;

use SurrealDB\SDK\Contracts\QueryExecutor;
use SurrealDB\SDK\Query\BoundQuery;
use SurrealDB\SDK\Types\RecordId;
use Surqlize\Attributes\Id;
use Surqlize\Attributes\Schema;
use Surqlize\Attributes\Table;
use Surqlize\Connection\ConnectionManager;
use Surqlize\Model\Exception\ModelNotFoundException;
use Surqlize\Model\Model;
use Surqlize\Model\ModelMetadata;
use Surqlize\Model\SchemaContract;
use Surqlize\Model\ValidationException;
use Surqlize\Query\Fields\ArrayField;
use Surqlize\Query\Fields\DateTimeField;
use Surqlize\Query\Fields\ObjectField;
use Surqlize\Query\Fields\RecordLinkField;
use Surqlize\Tests\Fixtures\Address;
use Surqlize\Tests\Fixtures\User;
use Surqlize\Tests\TestCase;

final class OrmRoadmapFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        ConnectionManager::reset();
        ModelMetadata::clear();

        parent::tearDown();
    }

    public function test_select_limit_start_and_first_scalar_use_bound_execution(): void
    {
        $executor = new CapturingExecutor(['beau']);

        $value = User::selectValue(fn ($user) => $user->name)
            ->where(fn ($user) => $user->age->gte(18))
            ->start(2)
            ->withExecutor($executor)
            ->first();

        $this->assertSame('beau', $value);
        $this->assertSame('SELECT VALUE name FROM user WHERE age >= $bind_0 LIMIT 1 START 2', $executor->queries[0]->query);
        $this->assertSame(['bind_0' => 18], $executor->queries[0]->bindings);
    }

    public function test_collect_models_hydrates_rows_and_infers_nested_model_casts(): void
    {
        $executor = new CapturingExecutor([
            [
                'id' => 'roadmap_user:beau',
                'name' => 'beau',
                'address' => [
                    'id' => 'roadmap_address:home',
                    'street' => 'Main',
                ],
            ],
        ]);

        $model = RoadmapUser::select(['*'])
            ->withExecutor($executor)
            ->collectModels()[0];

        $metadata = ModelMetadata::for(RoadmapUser::class);

        $this->assertSame(RoadmapAddress::class, $metadata->casts['address']);
        $this->assertInstanceOf(RoadmapUser::class, $model);
        $this->assertInstanceOf(RoadmapAddress::class, $model->address);
        $this->assertSame('Main', $model->address->street);
    }

    public function test_model_create_query_compiles_and_executes_with_bindings(): void
    {
        $executor = new CapturingExecutor([
            ['id' => 'user:beau', 'name' => 'beau', 'age' => 27],
        ]);

        $query = User::createQuery(['name' => 'beau', 'age' => 27], 'beau', $executor);

        $this->assertStringStartsWith('CREATE user:beau CONTENT', $query->compile());
        $this->assertStringEndsWith('RETURN AFTER', $query->compile());

        $model = $query->firstModel();

        $this->assertInstanceOf(User::class, $model);
        $this->assertSame('CREATE user:beau CONTENT $bind_0 RETURN AFTER', $executor->queries[0]->query);
        $this->assertSame(['name' => 'beau', 'age' => 27], $executor->queries[0]->bindings['bind_0']);
    }

    public function test_schema_rules_validate_before_persisting(): void
    {
        $this->expectException(ValidationException::class);

        ValidatedRoadmapModel::create(['name' => ''], executor: new CapturingExecutor([]));
    }

    public function test_transaction_batches_bound_orm_queries(): void
    {
        $executor = new CapturingExecutor([]);

        ConnectionManager::transaction(function ($transaction): void {
            User::select(['name'])
                ->where(fn ($user) => $user->name->eq('beau'))
                ->withExecutor($transaction)
                ->collect();

            User::createQuery(['name' => 'tobie', 'age' => 30], executor: $transaction)->execute();
        }, $executor);

        $this->assertSame(
            'BEGIN TRANSACTION; SELECT name FROM user WHERE name = $tx_0_bind_0; CREATE user CONTENT $tx_1_bind_0 RETURN AFTER; COMMIT TRANSACTION;',
            $executor->queries[0]->query,
        );
        $this->assertSame('beau', $executor->queries[0]->bindings['tx_0_bind_0']);
        $this->assertSame(['name' => 'tobie', 'age' => 30], $executor->queries[0]->bindings['tx_1_bind_0']);
    }

    public function test_richer_dynamic_field_types_are_resolved_from_property_types(): void
    {
        $fields = RoadmapRichFieldsModel::fields();

        $this->assertInstanceOf(DateTimeField::class, $fields->field('createdAt'));
        $this->assertInstanceOf(ArrayField::class, $fields->field('tags'));
        $this->assertInstanceOf(ObjectField::class, $fields->field('meta'));
        $this->assertInstanceOf(RecordLinkField::class, $fields->field('address'));
    }

    public function test_model_find_count_exists_and_refresh_use_model_scoped_queries(): void
    {
        $findExecutor = new CapturingExecutor([
            ['id' => 'user:beau', 'name' => 'beau', 'age' => 27],
        ]);

        $user = User::find('beau', $findExecutor);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('SELECT * FROM user WHERE id = $bind_0 LIMIT 1', $findExecutor->queries[0]->query);

        $countExecutor = new CapturingExecutor([['count' => 2]]);
        $this->assertSame(2, User::count(fn ($user) => $user->field('age')->gte(18), $countExecutor));
        $this->assertSame('SELECT count() AS count FROM user WHERE age >= $bind_0 GROUP ALL', $countExecutor->queries[0]->query);

        $existsExecutor = new CapturingExecutor([['id' => 'user:beau']]);
        $this->assertTrue(User::exists(fn ($user) => $user->field('name')->eq('beau'), $existsExecutor));
        $this->assertSame('SELECT id FROM user WHERE name = $bind_0 LIMIT 1', $existsExecutor->queries[0]->query);
    }

    public function test_find_or_fail_throws_model_not_found_exception(): void
    {
        $this->expectException(ModelNotFoundException::class);

        User::findOrFail('missing', new CapturingExecutor([]));
    }
}

final class CapturingExecutor implements QueryExecutor
{
    /** @var list<BoundQuery> */
    public array $queries = [];

    /**
     * @param list<mixed> $result
     */
    public function __construct(
        private readonly array $result,
    ) {}

    /**
     * @return list<mixed>
     */
    public function query(BoundQuery $query): array
    {
        $this->queries[] = $query;

        return $this->result;
    }
}

#[Table('roadmap_address')]
final class RoadmapAddress extends Model
{
    /** @var RecordId<'roadmap_address'> */
    #[Id] public RecordId $id;

    public string $street;
}

#[Table('roadmap_user')]
final class RoadmapUser extends Model
{
    /** @var RecordId<'roadmap_user'> */
    #[Id] public RecordId $id;

    public string $name;

    public ?RoadmapAddress $address = null;
}

#[Table('validated_roadmap')]
#[Schema(ValidatedRoadmapSchema::class)]
final class ValidatedRoadmapModel extends Model
{
    public string $name;
}

final class ValidatedRoadmapSchema implements SchemaContract
{
    public function definitions(): array
    {
        return [];
    }

    public function rules(): array
    {
        return [
            'name' => static fn (mixed $value): bool|string => is_string($value) && $value !== '' ? true : 'Name is required.',
        ];
    }
}

#[Table('roadmap_rich_fields')]
final class RoadmapRichFieldsModel extends Model
{
    public \DateTimeImmutable $createdAt;

    /** @var list<string> */
    public array $tags = [];

    public object $meta;

    public ?Address $address = null;
}
