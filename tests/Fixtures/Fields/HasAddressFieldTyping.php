<?php

declare(strict_types=1);

namespace Surqlize\Tests\Fixtures\Fields;

use Surqlize\Query\Fields\FieldSetRegistry;
use Surqlize\Query\ModelQuery;

trait HasAddressFieldTyping
{
    /**
     * @param list<string|mixed>|\Closure(HasAddressFields): mixed $fields
     *
     * @return ModelQuery<HasAddressFields>
     */
    public static function select(array|\Closure $fields = ['*']): ModelQuery
    {
        return ModelQuery::forFieldSet(static::class, new HasAddressFields(), $fields);
    }

    public static function fields(): HasAddressFields
    {
        $fields = FieldSetRegistry::resolve(static::class);

        if (! $fields instanceof HasAddressFields) {
            throw new \RuntimeException('Generated HasAddressFields adapter could not be resolved.');
        }

        return $fields;
    }
}
