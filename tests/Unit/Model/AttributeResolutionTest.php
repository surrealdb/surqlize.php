<?php

declare(strict_types=1);

namespace Surqlize\Tests\Unit\Model;

use Surqlize\Edge\EdgeMetadata;
use Surqlize\Model\ModelMetadata;
use Surqlize\Tests\Fixtures\Address;
use Surqlize\Tests\Fixtures\AddressSchema;
use Surqlize\Tests\Fixtures\HasAddress;
use Surqlize\Tests\Fixtures\User;
use Surqlize\Tests\Fixtures\UserSchema;
use Surqlize\Tests\TestCase;

/**
 * @group pending-api
 */
final class AttributeResolutionTest extends TestCase
{
    public function test_user_table_name(): void
    {
        self::requireApi(ModelMetadata::class);

        $metadata = ModelMetadata::for(User::class);

        $this->assertSame('user', $metadata->tableName);
    }

    public function test_user_schema_class(): void
    {
        self::requireApi(ModelMetadata::class);

        $metadata = ModelMetadata::for(User::class);

        $this->assertSame(UserSchema::class, $metadata->schemaClass);
    }

    public function test_user_id_property(): void
    {
        self::requireApi(ModelMetadata::class);

        $metadata = ModelMetadata::for(User::class);

        $this->assertSame('id', $metadata->idProperty);
    }

    public function test_user_casts_include_address(): void
    {
        self::requireApi(ModelMetadata::class);

        $metadata = ModelMetadata::for(User::class);

        $this->assertArrayHasKey('address', $metadata->casts);
        $this->assertSame(Address::class, $metadata->casts['address']);
    }

    public function test_address_table_name(): void
    {
        self::requireApi(ModelMetadata::class);

        $metadata = ModelMetadata::for(Address::class);

        $this->assertSame('address', $metadata->tableName);
    }

    public function test_address_schema_class(): void
    {
        self::requireApi(ModelMetadata::class);

        $metadata = ModelMetadata::for(Address::class);

        $this->assertSame(AddressSchema::class, $metadata->schemaClass);
    }

    public function test_has_address_edge_metadata(): void
    {
        self::requireApi(EdgeMetadata::class);

        $metadata = EdgeMetadata::for(HasAddress::class);

        $this->assertSame('has_address', $metadata->tableName);
        $this->assertSame(User::class, $metadata->inClass);
        $this->assertSame(Address::class, $metadata->outClass);
    }
}
