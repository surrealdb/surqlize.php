<?php

declare(strict_types=1);

namespace Surqlize\Tests\Unit;

use Surqlize\Attributes\Cast;
use Surqlize\Attributes\Schema;
use Surqlize\Attributes\Table;
use Surqlize\Edge\EdgeMetadata;
use Surqlize\Edge\GraphSelectField;
use Surqlize\Model\HydratesFromRow;
use Surqlize\Model\Hydrator;
use Surqlize\Model\Model;
use Surqlize\Model\ModelMetadata;
use Surqlize\Model\SchemaContract;
use Surqlize\Query\Ast\GraphDirection;
use Surqlize\Query\Fields\FieldSet;
use Surqlize\Query\ModelQuery;
use Surqlize\Tests\Fixtures\Address;
use Surqlize\Tests\Fixtures\HasAddress;
use Surqlize\Tests\Fixtures\User;
use Surqlize\Tests\TestCase;
use SurrealDB\SDK\Types\RecordId;

final class SecurityHardeningTest extends TestCase
{
    public function test_model_metadata_rejects_non_model_class_strings(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new \ReflectionMethod(ModelMetadata::class, 'for'))->invoke(null, \stdClass::class);
    }

    public function test_edge_metadata_rejects_non_edge_class_strings(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        EdgeMetadata::for(User::class);
    }

    public function test_cast_attribute_must_target_model_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ModelMetadata::for(InvalidCastTargetModel::class);
    }

    public function test_schema_attribute_must_target_schema_contract(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ModelMetadata::for(InvalidSchemaTargetModel::class);
    }

    public function test_table_attribute_must_be_safe_identifier(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ModelMetadata::for(UnsafeTableNameModel::class);
    }

    public function test_hydrator_rejects_required_constructor_without_factory(): void
    {
        $this->expectException(\LogicException::class);

        (new Hydrator())->hydrate(ConstructorOnlyModel::class, ['name' => 'beau']);
    }

    public function test_hydrator_uses_explicit_row_factory_for_required_constructor_models(): void
    {
        $model = (new Hydrator())->hydrate(FactoryHydratedModel::class, [
            'name' => 'beau',
            'ignored' => 'value',
        ]);

        $this->assertInstanceOf(FactoryHydratedModel::class, $model);
        $this->assertSame('beau', $model->name);
        $this->assertTrue($model->factoryUsed);
    }

    public function test_select_rejects_unsafe_field_selection(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        User::select(['name; DELETE user'])->compile();
    }

    public function test_where_rejects_unsafe_field_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        User::select(['name'])
            ->where(fn (FieldSet $fields) => $fields->field('name OR true')->eq('beau'))
            ->compile();
    }

    public function test_where_rejects_unsafe_operator(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        User::select(['name'])
            ->where(fn (FieldSet $fields) => $fields->where('name', 'OR true', 'beau'))
            ->compile();
    }

    public function test_for_table_rejects_unsafe_table_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ModelQuery::forTable('user; DELETE user', ['*'])->compile();
    }

    public function test_graph_alias_rejects_unsafe_identifier(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        GraphSelectField::fromEdge(HasAddress::class, GraphDirection::Out)
            ->out(Address::class)
            ->as('address; DELETE address')
            ->compile();
    }

    public function test_fetch_rejects_unsafe_field_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        User::select(['name'])->fetch('address; DELETE address')->compile();
    }

    public function test_order_by_rejects_unsafe_field_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        User::select(['name'])->orderBy('name; DELETE user')->compile();
    }

    public function test_relate_content_rejects_unsafe_keys(): void
    {
        $user = new User();
        $user->id = new RecordId('user', 'beau');

        $address = new Address();
        $address->id = new RecordId('address', 'home');

        $this->expectException(\InvalidArgumentException::class);

        User::relate($user)
            ->edge(HasAddress::class)
            ->with($address)
            ->content(['created_at; DELETE has_address' => true])
            ->compile();
    }
}

#[Table('invalid_cast_target')]
final class InvalidCastTargetModel extends Model
{
    #[Cast(Model::class)]
    public mixed $child;
}

#[Table('invalid_schema_target')]
#[Schema(SchemaContract::class)]
final class InvalidSchemaTargetModel extends Model
{
    public string $name;
}

#[Table('unsafe; DELETE user')]
final class UnsafeTableNameModel extends Model
{
    public string $name;
}

#[Table('constructor_only')]
final class ConstructorOnlyModel extends Model
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

#[Table('factory_hydrated')]
final class FactoryHydratedModel extends Model implements HydratesFromRow
{
    public string $name;

    public bool $factoryUsed = false;

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    /** @param array<string, mixed> $row */
    public static function fromSurqlizeRow(array $row): Model
    {
        $model = new self((string) $row['name']);
        $model->factoryUsed = true;

        return $model;
    }
}
