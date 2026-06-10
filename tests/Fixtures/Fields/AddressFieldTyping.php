<?php

declare(strict_types=1);

namespace Surqlize\Tests\Fixtures\Fields;

use Surqlize\Query\Fields\FieldSetRegistry;
use Surqlize\Query\ModelQuery;

trait AddressFieldTyping
{
    /**
     * @param list<string|mixed>|\Closure(AddressFields): mixed $fields
     *
     * @return ModelQuery<AddressFields>
     */
    public static function select(array|\Closure $fields = ['*']): ModelQuery
    {
        return ModelQuery::forFieldSet(static::class, new AddressFields(), $fields);
    }

    public static function fields(): AddressFields
    {
        $fields = FieldSetRegistry::resolve(static::class);

        if (! $fields instanceof AddressFields) {
            throw new \RuntimeException('Generated AddressFields adapter could not be resolved.');
        }

        return $fields;
    }
}
