<?php

declare(strict_types=1);

namespace Surqlize\Examples\Models\Fields;

use Surqlize\Examples\Models\HasAddress;
use Surqlize\Query\Fields\FieldSet;
use Surqlize\Query\Fields\RecordLinkField;

final class HasAddressFields extends FieldSet
{
    public readonly RecordLinkField $in;
    public readonly RecordLinkField $out;

    public function __construct()
    {
        parent::__construct(HasAddress::class);

        $this->in = new RecordLinkField('in');
        $this->out = new RecordLinkField('out');
    }
}
