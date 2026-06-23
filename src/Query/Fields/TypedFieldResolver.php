<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use Surqlize\Query\Ast\GraphTraversal;
use Surqlize\Query\Ast\SelectProjection;

final class TypedFieldResolver
{
    /**
     * @param class-string|string $modelClass
     *
     * @return list<string|GraphTraversal|SelectProjection>
     */
    public static function resolveSelection(string $modelClass, \Closure $callback): array
    {
        return self::resolveSelectionFor(FieldSetRegistry::resolve($modelClass), $callback);
    }

    /**
     * @return list<string|GraphTraversal|SelectProjection>
     */
    public static function resolveSelectionFor(FieldSet $fields, \Closure $callback): array
    {
        $result = $callback($fields);
        $items = is_array($result) ? $result : [$result];
        $resolved = [];

        $index = 0;

        foreach ($items as $item) {
            $resolved[] = self::fieldName($item, 'select()', $fields, $index);
            $index++;
        }

        return $resolved;
    }

    /**
     * @param class-string|string $modelClass
     */
    public static function resolveValueField(string $modelClass, \Closure $callback): string
    {
        return self::resolveValueFieldFor(FieldSetRegistry::resolve($modelClass), $callback);
    }

    public static function resolveValueFieldFor(FieldSet $fields, \Closure $callback): string
    {
        $result = $callback($fields);

        if (! $result instanceof Field) {
            throw new \InvalidArgumentException(
                sprintf(
                    'selectValue() typed callback for %s must return a Field; %s returned.',
                    self::fieldSetContext($fields),
                    get_debug_type($result),
                ),
            );
        }

        return $result->path();
    }

    /**
     * @param class-string|string $modelClass
     *
     * @return list<string>
     */
    public static function resolveFetchFields(string $modelClass, \Closure $callback): array
    {
        return self::resolveFetchFieldsFor(FieldSetRegistry::resolve($modelClass), $callback);
    }

    /**
     * @return list<string>
     */
    public static function resolveFetchFieldsFor(FieldSet $fields, \Closure $callback): array
    {
        $result = $callback($fields);
        $items = is_array($result) ? $result : [$result];
		$resolved = [];

        $index = 0;

		foreach ($items as $item) {
			if (! $item instanceof Field) {
				throw new \InvalidArgumentException(
					sprintf(
						'fetch() typed callback for %s must return Field values; %s found at index %d.',
						self::fieldSetContext($fields),
						get_debug_type($item),
						$index,
					),
				);
			}

			$resolved[] = $item->path();
            $index++;
		}

		return $resolved;
    }

    /**
     * @param class-string|string $modelClass
     *
     * @return list<OrderExpression>
     */
    public static function resolveOrder(string $modelClass, \Closure $callback): array
    {
        return self::resolveOrderFor(FieldSetRegistry::resolve($modelClass), $callback);
    }

    /**
     * @return list<OrderExpression>
     */
    public static function resolveOrderFor(
        FieldSet $fields,
        \Closure $callback,
        OrderDirection $direction = OrderDirection::Ascending,
    ): array
    {
        $result = $callback($fields);
        $items = is_array($result) ? $result : [$result];
        $resolved = [];

        $index = 0;

        foreach ($items as $item) {
            if ($item instanceof Field) {
                $resolved[] = new OrderExpression($item->path(), $direction);
                $index++;

                continue;
            }

            if (! $item instanceof OrderExpression) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'orderBy() typed callback for %s must return Field or OrderExpression values; %s found at index %d.',
                        self::fieldSetContext($fields),
                        get_debug_type($item),
                        $index,
                    ),
                );
            }

            $resolved[] = $item;
            $index++;
        }

        return $resolved;
    }

    /**
     * @return list<string>
     */
    public static function resolveFieldPathsFor(FieldSet $fields, \Closure $callback, string $operation): array
    {
        $result = $callback($fields);
        $items = is_array($result) ? $result : [$result];
        $resolved = [];

        $index = 0;

        foreach ($items as $item) {
            if (! $item instanceof Field) {
                throw new \InvalidArgumentException(
                    sprintf(
                        '%s typed callback for %s must return Field values; %s found at index %d.',
                        $operation,
                        self::fieldSetContext($fields),
                        get_debug_type($item),
                        $index,
                    ),
                );
            }

            $resolved[] = $item->path();
            $index++;
        }

        return $resolved;
    }

    private static function fieldName(mixed $field, string $operation, FieldSet $fields, int $index): string|GraphTraversal|SelectProjection
    {
        if ($field instanceof Field) {
            return $field->path();
        }

        if (is_string($field) || $field instanceof GraphTraversal || $field instanceof SelectProjection) {
            return $field;
        }

        throw new \InvalidArgumentException(
            sprintf(
                '%s typed callback for %s must return Field, string, GraphTraversal, or SelectProjection values; %s found at index %d.',
                $operation,
                self::fieldSetContext($fields),
                get_debug_type($field),
                $index,
            ),
        );
    }

    private static function fieldSetContext(FieldSet $fields): string
    {
        $modelClass = $fields->modelClass();

        return $modelClass !== '' ? sprintf('model "%s"', $modelClass) : 'anonymous field set';
    }
}
