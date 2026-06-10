<?php

declare(strict_types=1);

namespace Surqlize\Examples\Models\Fields;

use Surqlize\Examples\Models\User;
use Surqlize\Query\Fields\Field;
use Surqlize\Query\Fields\FieldSet;
use Surqlize\Query\Fields\NumericField;
use Surqlize\Query\Fields\RecordIdField;
use Surqlize\Query\Fields\StringField;

final class UserFields extends FieldSet
{
    public readonly RecordIdField $id;
    public readonly StringField $name;
    public readonly NumericField $age;
    public readonly Field $address;

    public function __construct()
    {
        parent::__construct(User::class);

        $this->id = new RecordIdField('id', table: 'user');
        $this->name = new StringField('name');
        $this->age = new NumericField('age');
        $this->address = new Field('address');
    }
}
