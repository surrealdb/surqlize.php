<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use Surqlize\Query\Ast\WhereCondition;
use Surqlize\Query\Ast\WherePredicate;

final class TypedWhereResolver
{
    /**
     * @param class-string|string $modelClass
     *
     * @return list<WherePredicate>
     */
    public static function resolve(string $modelClass, \Closure $callback): array
    {
        return self::resolveFor(FieldSetRegistry::resolve($modelClass), $callback);
    }

    /**
     * @return list<WherePredicate>
     */
    public static function resolveFor(FieldSet $fields, \Closure $callback): array
    {
        return self::normalize($callback($fields), sprintf('where() typed callback for %s', self::fieldSetContext($fields)));
    }

    /**
     * @return list<WherePredicate>
     */
    public static function normalize(mixed $result, string $context = 'typed where callback'): array
    {
        if ($result instanceof WherePredicate) {
            return [$result];
        }

        if (is_array($result)) {
            $conditions = [];

            foreach (array_values($result) as $index => $item) {
                if (! $item instanceof WherePredicate) {
                    throw new \InvalidArgumentException(
                        sprintf('%s must return WherePredicate values; %s found at index %d.', $context, get_debug_type($item), $index),
                    );
                }

                $conditions[] = $item;
            }

            return $conditions;
        }

        throw new \InvalidArgumentException(
            sprintf('%s must return a WherePredicate or list of WherePredicate; %s returned.', $context, get_debug_type($result)),
        );
    }

    private static function fieldSetContext(FieldSet $fields): string
    {
        $modelClass = $fields->modelClass();

        return $modelClass !== '' ? sprintf('model "%s"', $modelClass) : 'anonymous field set';
    }
}
