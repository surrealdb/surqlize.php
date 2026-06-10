<?php

declare(strict_types=1);

namespace Surqlize\Tests\Unit\Generator;

use Surqlize\Generator\FieldClassGenerator;
use Surqlize\Generator\FieldTypingTraitGenerator;
use Surqlize\Query\Fields\Field;
use Surqlize\Query\Fields\NumericField;
use Surqlize\Query\Fields\RecordIdField;
use Surqlize\Query\Fields\StringField;
use Surqlize\Tests\Fixtures\Address;
use Surqlize\Tests\Fixtures\HasAddress;
use Surqlize\Tests\Fixtures\User;
use Surqlize\Tests\TestCase;

final class FieldClassGeneratorTest extends TestCase
{
    public function test_generates_user_field_adapter_from_model_properties(): void
    {
        $source = (new FieldClassGenerator())->generate(User::class, 'Generated\\Fields');

        $this->assertStringContainsString('final class UserFields extends FieldSet', $source);
        $this->assertStringContainsString('public readonly RecordIdField $id;', $source);
        $this->assertStringContainsString('public readonly StringField $name;', $source);
        $this->assertStringContainsString('public readonly NumericField $age;', $source);
        $this->assertStringContainsString('public readonly Field $address;', $source);
        $this->assertStringContainsString('$this->id = new RecordIdField(\'id\', table: \'user\');', $source);
    }

    public function test_generates_address_field_adapter_from_model_properties(): void
    {
        $source = (new FieldClassGenerator())->generate(Address::class, 'Generated\\Fields');

        $this->assertStringContainsString('public readonly StringField $postcode;', $source);
        $this->assertStringContainsString('$this->id = new RecordIdField(\'id\', table: \'address\');', $source);
    }

    public function test_generates_edge_field_adapter_without_treating_endpoints_as_model_ids(): void
    {
        $source = (new FieldClassGenerator())->generate(HasAddress::class, 'Generated\\Fields');

        $this->assertStringContainsString('final class HasAddressFields extends FieldSet', $source);
        $this->assertStringContainsString('public readonly Field $in;', $source);
        $this->assertStringContainsString('public readonly Field $out;', $source);
        $this->assertStringNotContainsString('new RecordIdField(\'in\'', $source);
        $this->assertStringNotContainsString('new RecordIdField(\'out\'', $source);
    }

    public function test_fixture_fields_use_expected_field_types(): void
    {
        $userFields = User::fields();

        $this->assertInstanceOf(RecordIdField::class, $userFields->id);
        $this->assertInstanceOf(StringField::class, $userFields->name);
        $this->assertInstanceOf(NumericField::class, $userFields->age);
        $this->assertInstanceOf(Field::class, $userFields->address);
    }

    public function test_generates_field_typing_trait_for_static_analysis(): void
    {
        $source = (new FieldTypingTraitGenerator())->generate(User::class, 'Generated\\Fields');

        $this->assertStringContainsString('trait UserFieldTyping', $source);
        $this->assertStringContainsString('@return ModelQuery<UserFields>', $source);
        $this->assertStringContainsString('ModelQuery::forFieldSet(static::class, new UserFields(), $fields)', $source);
        $this->assertStringContainsString('public static function fields(): UserFields', $source);
    }
}
