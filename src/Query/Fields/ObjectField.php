<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use Surqlize\Query\Compiler\Identifier;

/** Field reference for embedded object fields and nested object paths. */
final class ObjectField extends Field
{
    public function field(string $name): Field
    {
        return new Field($this->path() . '.' . Identifier::field($name, 'nested object field'));
    }

    public function string(string $name): StringField
    {
        return new StringField($this->path() . '.' . Identifier::field($name, 'nested string field'));
    }

    public function number(string $name): NumericField
    {
        return new NumericField($this->path() . '.' . Identifier::field($name, 'nested numeric field'));
    }

    public function boolean(string $name): BooleanField
    {
        return new BooleanField($this->path() . '.' . Identifier::field($name, 'nested boolean field'));
    }
}
