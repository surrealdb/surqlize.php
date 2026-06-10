<?php

declare(strict_types=1);

namespace Surqlize\Generator;

use ReflectionClass;
use ReflectionProperty;
use Surqlize\Model\Model;
use Surqlize\Model\ModelMetadata;
use Surqlize\Query\Compiler\Identifier;
use Surqlize\Query\Fields\Field;
use Surqlize\Query\Fields\FieldSet;
use Surqlize\Query\Fields\RecordIdField;
use Surqlize\Support\ClassString;

final class FieldClassGenerator
{
    public function __construct(
        private readonly FieldTypeMapper $typeMapper = new FieldTypeMapper(),
    ) {}

    /**
     * @param class-string<Model> $modelClass
     */
    public function generate(string $modelClass, string $namespace): string
    {
        $modelClass = ClassString::model($modelClass);
        $namespace = GeneratedPhp::namespace($namespace);
        $metadata = ModelMetadata::for($modelClass);
        $reflection = new ReflectionClass($modelClass);
        $shortModelName = $reflection->getShortName();
        $fieldClassName = $shortModelName . 'Fields';

        $fieldProperties = [];
        $fieldAssignments = [];
        $imports = [
            FieldSet::class,
            $modelClass,
        ];

        foreach ($this->publicInstanceProperties($reflection) as $property) {
            $fieldClass = $this->typeMapper->fieldClassFor($property, $metadata);
            $imports[] = $fieldClass;

            $shortFieldClass = $this->shortName($fieldClass);
            $name = $property->getName();
            $fieldProperties[] = sprintf('    public readonly %s $%s;', $shortFieldClass, $name);
            $fieldAssignments[] = $this->fieldAssignment($name, $fieldClass, $metadata->tableName);
        }

        $imports = $this->compileImports($imports);

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

{$imports}

final class {$fieldClassName} extends FieldSet
{
{$this->join($fieldProperties)}

    public function __construct()
    {
        parent::__construct({$shortModelName}::class);

{$this->join($fieldAssignments)}
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
            $source = $this->generate($modelClass, $config->fieldsNamespace);
            $path = rtrim($config->fieldsPath, DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . $this->shortName($modelClass)
                . 'Fields.php';

            if (file_put_contents($path, $source) === false) {
                throw new \RuntimeException(sprintf('Unable to write generated field adapter for model "%s" to "%s".', $modelClass, $path));
            }
        }
    }

    /**
     * @return list<ReflectionProperty>
     *
     * @param ReflectionClass<Model> $reflection
     */
    private function publicInstanceProperties(ReflectionClass $reflection): array
    {
        return array_values(array_filter(
            $reflection->getProperties(ReflectionProperty::IS_PUBLIC),
            static fn (ReflectionProperty $property): bool => ! $property->isStatic(),
        ));
    }

    /**
     * @param class-string<Field> $fieldClass
     */
    private function fieldAssignment(string $name, string $fieldClass, string $tableName): string
    {
        $name = Identifier::field($name);
        $tableName = Identifier::table($tableName);
        $shortFieldClass = $this->shortName($fieldClass);
        $fieldLiteral = GeneratedPhp::stringLiteral($name);

        if ($fieldClass === RecordIdField::class) {
            return sprintf(
                '        $this->%s = new %s(%s, table: %s);',
                $name,
                $shortFieldClass,
                $fieldLiteral,
                GeneratedPhp::stringLiteral($tableName),
            );
        }

        return sprintf('        $this->%s = new %s(%s);', $name, $shortFieldClass, $fieldLiteral);
    }

    /**
     * @param list<class-string> $classes
     */
    private function compileImports(array $classes): string
    {
        $classes = array_values(array_unique($classes));
        sort($classes);

        return implode(
            "\n",
            array_map(static fn (string $class): string => 'use ' . $class . ';', $classes),
        );
    }

    private function shortName(string $class): string
    {
        $position = strrpos($class, '\\');

        return $position === false ? $class : substr($class, $position + 1);
    }

    /**
     * @param list<string> $lines
     */
    private function join(array $lines): string
    {
        return implode("\n", $lines);
    }
}
