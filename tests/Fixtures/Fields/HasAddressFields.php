<?php

declare(strict_types=1);

namespace Surqlize\Tests\Fixtures\Fields;

use Surqlize\Query\Fields\FieldSet;
use Surqlize\Query\Fields\RecordLinkField;
use Surqlize\Tests\Fixtures\HasAddress;

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
