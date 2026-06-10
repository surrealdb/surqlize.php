<?php

declare(strict_types=1);

namespace Surqlize\Query\Support;

use ReflectionClass;
use Surqlize\Attributes\Edge as EdgeAttribute;
use Surqlize\Attributes\Table;
use Surqlize\Query\Compiler\Identifier;
use Surqlize\Query\Support\Exception\MissingTableNameAttributeException;
use Surqlize\Support\ClassString;

/**
 * Resolves SurrealDB table names from model/edge classes without depending on the Model layer.
 */
final class TableNameResolver
{
    /**
     * @param class-string $modelClass
     */
    public static function resolve(string $modelClass): string
    {
        $modelClass = ClassString::existing($modelClass, 'Table name target');
        $reflection = new ReflectionClass($modelClass);
        $tableAttributes = $reflection->getAttributes(Table::class);

        if ($tableAttributes !== []) {
            if (count($tableAttributes) > 1) {
                throw new \InvalidArgumentException(
                    sprintf('Class "%s" defines multiple #[Table] attributes.', $modelClass),
                );
            }

            /** @var Table $table */
            $table = $tableAttributes[0]->newInstance();

            return Identifier::table($table->name, 'Table name from #[Table] on class "' . $modelClass . '"');
        }

        $edgeAttributes = $reflection->getAttributes(EdgeAttribute::class);

        if ($edgeAttributes !== []) {
            if (count($edgeAttributes) > 1) {
                throw new \InvalidArgumentException(
                    sprintf('Class "%s" defines multiple #[Edge] attributes.', $modelClass),
                );
            }

            /** @var EdgeAttribute $edge */
            $edge = $edgeAttributes[0]->newInstance();

            return Identifier::table($edge->name, 'Table name from #[Edge] on class "' . $modelClass . '"');
        }

        throw new MissingTableNameAttributeException($modelClass);
    }
}
