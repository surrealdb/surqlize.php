<?php

declare(strict_types=1);

namespace Surqlize\Tests\Unit\Relate;

use Surqlize\Model\Model;
use Surqlize\Relate\RelateBuilder;
use Surqlize\Relate\Time;
use Surqlize\Tests\Fixtures\Address;
use Surqlize\Tests\Fixtures\HasAddress;
use Surqlize\Tests\Fixtures\User;
use Surqlize\Tests\TestCase;
use SurrealDB\SDK\Types\RecordId;

/**
 * @group pending-api
 */
final class RelateCompileTest extends TestCase
{
    public function test_relate_with_content(): void
    {
        self::requireApi(Model::class, RelateBuilder::class);

        $user = new User();
        $user->id = RecordId::from('user', 'u1');

        $address = new Address();
        $address->id = RecordId::from('address', 'a1');

        $this->assertSame(
            'RELATE user:u1->has_address->address:a1 CONTENT { incremental: 1 }',
            User::relate($user)
                ->edge(HasAddress::class)
                ->with($address)
                ->content(['incremental' => 1])
                ->compile(),
        );
    }

    public function test_relate_with_timeout(): void
    {
        self::requireApi(Model::class, RelateBuilder::class, Time::class);

        $user = new User();
        $user->id = RecordId::from('user', 'u1');

        $address = new Address();
        $address->id = RecordId::from('address', 'a1');

        $this->assertSame(
            'RELATE user:u1->has_address->address:a1 TIMEOUT 30s',
            User::relate($user)
                ->edge(HasAddress::class)
                ->with($address)
                ->timeout(30, Time::Seconds)
                ->compile(),
        );
    }

    public function test_relate_rejects_non_model_target(): void
    {
        self::requireApi(Model::class, RelateBuilder::class);

        $this->expectException(\TypeError::class);

        /** @phpstan-ignore argument.type */
        User::relate(HasAddress::class);
    }

}
