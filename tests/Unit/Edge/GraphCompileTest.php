<?php

declare(strict_types=1);

namespace Surqlize\Tests\Unit\Edge;

use Surqlize\Edge\Edge;
use Surqlize\Edge\GraphBracketQuery;
use Surqlize\Edge\GraphSelectField;
use Surqlize\Query\Ast\GraphDirection;
use Surqlize\Model\Model;
use Surqlize\Query\Operator;
use Surqlize\Tests\Fixtures\Address;
use Surqlize\Tests\Fixtures\HasAddress;
use Surqlize\Tests\Fixtures\User;
use Surqlize\Tests\TestCase;

/**
 * @group pending-api
 */
final class GraphCompileTest extends TestCase
{
    public function test_graph_out_direction_compiles_arrow_syntax(): void
    {
        self::requireApi(Edge::class, Model::class);

        $field = GraphSelectField::fromEdge(HasAddress::class, GraphDirection::Out)
            ->out(Address::class, fn ($address) => $address->postcode->includes('24'))
            ->as('address')
            ->fetch();

        $this->assertSame(
            '->has_address->address[WHERE postcode INCLUDES \'24\'] AS address',
            $field->compile(),
        );
    }

    public function test_graph_out_direction_accepts_typed_field_callback(): void
    {
        $field = GraphSelectField::fromEdge(HasAddress::class, GraphDirection::Out)
            ->out(Address::class, fn ($address) => $address->postcode->includes('24'))
            ->as('address')
            ->fetch();

        $this->assertSame(
            '->has_address->address[WHERE postcode INCLUDES \'24\'] AS address',
            $field->compile(),
        );
    }

    public function test_graph_out_direction_keeps_explicit_legacy_bracket_query(): void
    {
        $field = GraphSelectField::fromEdge(HasAddress::class, GraphDirection::Out)
            ->out(Address::class, fn (GraphBracketQuery $query) => $query->where('postcode', 'INCLUDES', '24'))
            ->as('address')
            ->fetch();

        $this->assertSame(
            '->has_address->address[WHERE postcode INCLUDES \'24\'] AS address',
            $field->compile(),
        );
    }

    public function test_graph_field_embedded_in_select(): void
    {
        self::requireApi(Edge::class, Model::class, Operator::class);

        $this->assertSame(
            'SELECT name, ->has_address->address[WHERE postcode INCLUDES \'24\'] AS address WHERE name = "beau"',
            User::select([
                'name',
                GraphSelectField::fromEdge(HasAddress::class, GraphDirection::Out)
                    ->out(Address::class, fn ($address) => $address->postcode->includes('24'))
                    ->as('address')
                    ->fetch(),
            ])
                ->where(fn ($user) => $user->name->eq('beau'))
                ->compile(),
        );
    }

    public function test_graph_in_direction_compiles_reverse_arrow_syntax(): void
    {
        self::requireApi(Edge::class);

        $field = GraphSelectField::fromEdge(HasAddress::class, GraphDirection::In)
            ->in(User::class)
            ->as('user');

        $this->assertSame(
            '<-has_address<-user AS user',
            $field->compile(),
        );
    }
}
