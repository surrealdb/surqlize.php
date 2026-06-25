<?php

declare(strict_types=1);

namespace Surqlize\Tests\Unit\Query;

use Surqlize\Query\Fields\Projection;
use Surqlize\Tests\Fixtures\User;
use Surqlize\Tests\TestCase;

final class AdvancedSelectCompileTest extends TestCase
{
    public function test_advanced_select_clauses_compile_in_surrealql_order(): void
    {
        $this->assertSame(
            'SELECT * OMIT password FROM user WITH INDEX idx_user_email WHERE age >= 18 SPLIT tags ORDER BY name DESC LIMIT 10 START 20 FETCH address TIMEOUT 5s TEMPFILES EXPLAIN FULL',
            User::select(['*'])
                ->omit('password')
                ->withIndex('idx_user_email')
                ->where(fn ($user) => $user->age->gte(18))
                ->split('tags')
                ->orderBy(fn ($user) => $user->name->desc())
                ->limit(10)
                ->start(20)
                ->fetch(fn ($user) => $user->address)
                ->timeout(5)
                ->tempfiles()
                ->explain(full: true)
                ->compile(),
        );
    }

    public function test_group_by_and_projection_aliases_compile(): void
    {
        $this->assertSame(
            'SELECT age, count() AS total FROM user GROUP BY age ORDER BY total DESC',
            User::select(fn ($user) => [$user->age, Projection::count()->as('total')])
                ->groupBy(fn ($user) => $user->age)
                ->orderBy('total', 'DESC')
                ->compile(),
        );
    }

    public function test_group_and_split_are_mutually_exclusive(): void
    {
        $this->expectException(\LogicException::class);

        User::select(['*'])->split('tags')->groupAll();
    }
}
