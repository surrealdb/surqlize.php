<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use Surqlize\Model\ModelMetadata;
use Surqlize\Model\Model;
use Surqlize\Query\Ast\WhereCondition;
use Surqlize\Query\Compiler\Identifier;
use Surqlize\Query\Operator;

class FieldSet
{
    /** @var array<string, Field> */
    private array $dynamicFields = [];

    /**
     * @param class-string|string $modelClass
     */
    public function __construct(
        private readonly string $modelClass = '',
    ) {}

    /**
     * @return class-string|string
     */
    public function modelClass(): string
    {
        return $this->modelClass;
    }

    public function field(string $name): Field
    {
        $name = Identifier::field($name, sprintf('dynamic field for "%s"', $this->modelClass ?: 'anonymous field set'));

        return $this->dynamicFields[$name] ??= $this->makeField($name);
    }

    /**
     * Deprecated compatibility helper for legacy builder-style graph callbacks.
     */
    public function where(string $field, Operator|string $operator, mixed $value): WhereCondition
    {
        $field = Identifier::field($field, sprintf('legacy where() field for "%s"', $this->modelClass ?: 'anonymous field set'));

        return new WhereCondition($field, $operator, $value);
    }

    public function __get(string $name): Field
    {
        return $this->field($name);
    }

    private function makeField(string $name): Field
    {
        $metadata = $this->metadata();

        if ($metadata !== null && $metadata->idProperty === $name) {
            return new RecordIdField($name, $metadata->tableName);
        }

		$type = $metadata?->propertyTypes[$name] ?? null;

        if ($type !== null && is_a($type, \DateTimeInterface::class, true)) {
            return new DateTimeField($name);
        }

        if ($type === \SurrealDB\SDK\Types\RecordId::class || ($type !== null && is_subclass_of($type, Model::class))) {
            return new RecordLinkField($name);
        }

        return match ($type) {
            'string' => new StringField($name),
            'int', 'float' => new NumericField($name),
            'bool' => new BooleanField($name),
            'array' => new ArrayField($name),
            'object' => new ObjectField($name),
            default => new Field($name),
        };
    }

    private function metadata(): ?ModelMetadata
    {
        if (! is_subclass_of($this->modelClass, Model::class)) {
            return null;
        }

        return ModelMetadata::for($this->modelClass);
    }
}
