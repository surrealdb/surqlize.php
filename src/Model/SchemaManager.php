<?php

declare(strict_types=1);

namespace Surqlize\Model;

use SurrealDB\SDK\Contracts\QueryExecutor;
use SurrealDB\SDK\Query\BoundQuery;
use Surqlize\Connection\ConnectionManager;
use Surqlize\Schema\SchemaDefinition;
use Surqlize\Support\ClassString;

final class SchemaManager
{
    /**
     * @param list<class-string<Model>> $modelClasses
     *
     * @return list<string>
     */
    public function definitions(array $modelClasses): array
    {
        $definitions = [];

        foreach ($modelClasses as $modelClass) {
            $modelClass = ClassString::model($modelClass, 'Schema model class');
            $metadata = ModelMetadata::for($modelClass);

            if ($metadata->schemaClass === null) {
                continue;
            }

            $schema = new $metadata->schemaClass();

            foreach ($schema->definitions() as $definition) {
                if ($definition instanceof SchemaDefinition) {
                    $definitions = [...$definitions, ...$definition->definitions()];
                    continue;
                }

                $definitions[] = $definition;
            }
        }

        return $definitions;
    }

    /**
     * @param list<class-string<Model>> $modelClasses
     */
    public function apply(array $modelClasses, ?QueryExecutor $executor = null): void
    {
        $executor ??= ConnectionManager::get();

        foreach ($this->definitions($modelClasses) as $definition) {
            $executor->query(new BoundQuery($definition));
        }
    }
}
