<?php

declare(strict_types=1);

namespace Surqlize\Tests\Unit\Query;

use Surqlize\Edge\Edge;
use Surqlize\Edge\GraphSelectField;
use Surqlize\Model\Model;
use Surqlize\Query\Fields\FieldSet;
use Surqlize\Query\ModelQuery;
use Surqlize\Tests\Fixtures\Address;
use Surqlize\Tests\Fixtures\HasAddress;
use Surqlize\Tests\Fixtures\User;
use Surqlize\Tests\TestCase;
use SurrealDB\SDK\Types\RecordId;

/**
 * @group pending-api
 */
final class CompileTest extends TestCase
{
    public function test_simple_select_with_where(): void
    {
        self::requireApi(Model::class);
        self::requireMethod(ModelQuery::class, 'where');

        $this->assertSame(
            'SELECT name FROM user WHERE name = "beau"',
            User::select(['name'])->where(fn ($user) => $user->name->eq('beau'))->compile(),
        );
    }

    public function test_typed_select_with_where(): void
    {
        $this->assertSame(
            'SELECT name FROM user WHERE name = "beau"',
            User::select(fn ($user) => [$user->name])
                ->where(fn ($user) => $user->name->eq('beau'))
                ->compile(),
        );
    }

    public function test_typed_where_list_is_and_combined(): void
    {
        $this->assertSame(
            'SELECT name FROM user WHERE name = "beau" AND age >= 18',
            User::select(fn ($user) => [$user->name])
                ->where(fn ($user) => [
                    $user->name->eq('beau'),
                    $user->age->gte(18),
                ])
                ->compile(),
        );
    }

    public function test_typed_record_id_where_supports_string_integer_and_array_ids(): void
    {
        $this->assertSame(
            'SELECT name FROM user WHERE id = user:beau',
            User::select(fn ($user) => [$user->name])
                ->where(fn ($user) => $user->id->eq('beau'))
                ->compile(),
        );

        $this->assertSame(
            'SELECT name FROM user WHERE id = user:123',
            User::select(fn ($user) => [$user->name])
                ->where(fn ($user) => $user->id->eq(123))
                ->compile(),
        );

        $recordId = new RecordId('user', ['log', 123]);

        $this->assertSame(
            'SELECT name FROM user WHERE id = ' . $recordId->escape(),
            User::select(fn ($user) => [$user->name])
                ->where(fn ($user) => $user->id->eq(['log', 123]))
                ->compile(),
        );
    }

    public function test_typed_order_by_and_fetch(): void
    {
        $this->assertSame(
            'SELECT name, age FROM user WHERE age >= 18 ORDER BY name ASC FETCH address',
            User::select(fn ($user) => [$user->name, $user->age])
                ->where(fn ($user) => $user->age->gte(18))
                ->orderBy(fn ($user) => $user->name->asc())
                ->fetch(fn ($user) => $user->address)
                ->compile(),
        );
    }

    public function test_typed_order_by_accepts_field_return(): void
    {
        $this->assertSame(
            'SELECT name FROM user ORDER BY name ASC',
            User::select(fn ($user) => [$user->name])
                ->orderBy(fn ($user) => $user->name)
                ->compile(),
        );

        $this->assertSame(
            'SELECT name FROM user ORDER BY name DESC',
            User::select(fn ($user) => [$user->name])
                ->orderBy(fn ($user) => $user->name, 'DESC')
                ->compile(),
        );
    }

    public function test_edge_in_select_with_where(): void
    {
        self::requireApi(Model::class, Edge::class);

        $this->assertSame(
            'SELECT name FROM user WHERE age > 27',
            (new HasAddress())
                ->in()
                ->select(['name'])
                ->where(fn (FieldSet $user) => $user->field('age')->gt(27))
                ->compile(),
        );
    }

    public function test_edge_in_select_with_typed_where(): void
    {
        $this->assertSame(
            'SELECT name FROM user WHERE age > 27',
            (new HasAddress())
                ->in()
                ->select(fn ($user) => [$user->name])
                ->where(fn (FieldSet $user) => $user->field('age')->gt(27))
                ->compile(),
        );
    }

    public function test_edge_in_select_value_with_where(): void
    {
        self::requireApi(Model::class, Edge::class, ModelQuery::class);

        $this->assertSame(
            'SELECT VALUE name FROM user WHERE age > 27',
            (new HasAddress())
                ->in()
                ->selectValue('name')
                ->where(fn (FieldSet $user) => $user->field('age')->gt(27))
                ->compile(),
        );
    }

    public function test_typed_select_value(): void
    {
        $this->assertSame(
            'SELECT VALUE name FROM user WHERE age > 27',
            (new HasAddress())
                ->in()
                ->selectValue(fn ($user) => $user->name)
                ->where(fn (FieldSet $user) => $user->field('age')->gt(27))
                ->compile(),
        );
    }

    public function test_graph_select_with_fetch(): void
    {
        self::requireApi(Model::class, Edge::class);
        self::requireMethod(ModelQuery::class, 'where');
        self::requireMethod(ModelQuery::class, 'fetch');

        $this->assertSame(
            'SELECT name, ->has_address->address[WHERE postcode INCLUDES \'24\'] AS address WHERE name = "beau" FETCH address',
            User::select([
                'name',
                GraphSelectField::fromEdge(HasAddress::class, \Surqlize\Query\Ast\GraphDirection::Out)
                    ->out(Address::class, fn ($address) => $address->postcode->includes('24'))
                    ->as('address')
                    ->fetch(),
            ])
                ->where(fn ($user) => $user->name->eq('beau'))
                ->fetch('address')
                ->compile(),
        );
    }

    public function test_edge_select_in_out_with_fetch(): void
    {
        self::requireApi(Model::class, Edge::class);
        self::requireMethod(ModelQuery::class, 'fetch');

        $this->assertSame(
            'SELECT in, out FROM has_address FETCH in, out',
            HasAddress::select(['in', 'out'])->fetch(['in', 'out'])->compile(),
        );
    }
}
