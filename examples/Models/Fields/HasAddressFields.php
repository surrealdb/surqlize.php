<?php

declare(strict_types=1);

namespace Surqlize\Examples\Models\Fields;

use Surqlize\Examples\Models\HasAddress;
use Surqlize\Query\Fields\Field;
use Surqlize\Query\Fields\FieldSet;

final class HasAddressFields extends FieldSet
{
    public readonly Field $in;
    public readonly Field $out;

    public function __construct()
    {
        parent::__construct(HasAddress::class);

        $this->in = new Field('in');
        $this->out = new Field('out');
    }
}
