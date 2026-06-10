<?php

declare(strict_types=1);

namespace Surqlize\Generator;

use ReflectionClass;
use Surqlize\Model\Model;
use Surqlize\Support\ClassString;

final class FieldTypingTraitGenerator
{
    /**
     * @param class-string<Model> $modelClass
     */
    public function generate(string $modelClass, string $fieldsNamespace): string
    {
        $modelClass = ClassString::model($modelClass);
        $fieldsNamespace = GeneratedPhp::namespace($fieldsNamespace);
        $reflection = new ReflectionClass($modelClass);
        $shortModelName = $reflection->getShortName();
        $fieldClassName = $shortModelName . 'Fields';
        $traitName = $shortModelName . 'FieldTyping';

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$fieldsNamespace};

use Surqlize\Query\Fields\FieldSetRegistry;
use Surqlize\Query\ModelQuery;

trait {$traitName}
{
    /**
     * @param list<string|mixed>|\Closure({$fieldClassName}): mixed \$fields
     *
     * @return ModelQuery<{$fieldClassName}>
     */
    public static function select(array|\Closure \$fields = ['*']): ModelQuery
    {
        return ModelQuery::forFieldSet(static::class, new {$fieldClassName}(), \$fields);
    }

    /**
     * @param string|\Closure({$fieldClassName}): \Surqlize\Query\Fields\Field \$field
     *
     * @return ModelQuery<{$fieldClassName}>
     */
    public static function selectValue(string|\Closure \$field): ModelQuery
    {
        return ModelQuery::forValueFieldSet(static::class, new {$fieldClassName}(), \$field);
    }

    public static function fields(): {$fieldClassName}
    {
        \$fields = FieldSetRegistry::resolve(static::class);

        if (! \$fields instanceof {$fieldClassName}) {
            throw new \RuntimeException('Expected generated {$fieldClassName} adapter for model ' . static::class . '; run "surqlize generate:fields" or register it with FieldSetRegistry::register().');
        }

        return \$fields;
    }
}

PHP;
    }

    public function write(FieldGenerationConfig $config): void
    {
        if (! is_dir($config->fieldsPath) && ! mkdir($config->fieldsPath, 0775, true) && ! is_dir($config->fieldsPath)) {
            throw new \RuntimeException(sprintf('Unable to create fields directory "%s".', $config->fieldsPath));
        }

        foreach ($config->models as $modelClass) {
            $path = rtrim($config->fieldsPath, DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . $this->shortName($modelClass)
                . 'FieldTyping.php';

            if (file_put_contents($path, $this->generate($modelClass, $config->fieldsNamespace)) === false) {
                throw new \RuntimeException(sprintf('Unable to write generated field typing trait for model "%s" to "%s".', $modelClass, $path));
            }
        }
    }

    private function shortName(string $class): string
    {
        $position = strrpos($class, '\\');

        return $position === false ? $class : substr($class, $position + 1);
    }
}
