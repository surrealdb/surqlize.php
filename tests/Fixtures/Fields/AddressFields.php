<?php

declare(strict_types=1);

namespace Surqlize\Tests\Fixtures\Fields;

use Surqlize\Query\Fields\NumericField;
use Surqlize\Query\Fields\RecordIdField;
use Surqlize\Query\Fields\StringField;
use Surqlize\Query\Fields\FieldSet;
use Surqlize\Tests\Fixtures\Address;

final class AddressFields extends FieldSet
{
    public readonly RecordIdField $id;
    public readonly StringField $street;
    public readonly NumericField $number;
    public readonly StringField $postcode;

    public function __construct()
    {
        parent::__construct(Address::class);

        $this->id = new RecordIdField('id', table: 'address');
        $this->street = new StringField('street');
        $this->number = new NumericField('number');
        $this->postcode = new StringField('postcode');
    }
}
