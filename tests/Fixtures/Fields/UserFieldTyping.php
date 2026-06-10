<?php

declare(strict_types=1);

namespace Surqlize\Tests\Fixtures\Fields;

use Surqlize\Query\Fields\FieldSetRegistry;
use Surqlize\Query\ModelQuery;

trait UserFieldTyping
{
    /**
     * @param list<string|mixed>|\Closure(UserFields): mixed $fields
     *
     * @return ModelQuery<UserFields>
     */
    public static function select(array|\Closure $fields = ['*']): ModelQuery
    {
        return ModelQuery::forFieldSet(static::class, new UserFields(), $fields);
    }

    /**
     * @param string|\Closure(UserFields): \Surqlize\Query\Fields\Field $field
     *
     * @return ModelQuery<UserFields>
     */
    public static function selectValue(string|\Closure $field): ModelQuery
    {
        return ModelQuery::forValueFieldSet(static::class, new UserFields(), $field);
    }

    public static function fields(): UserFields
    {
        $fields = FieldSetRegistry::resolve(static::class);

        if (! $fields instanceof UserFields) {
            throw new \RuntimeException('Generated UserFields adapter could not be resolved.');
        }

        return $fields;
    }
}
