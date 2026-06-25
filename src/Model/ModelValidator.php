<?php

declare(strict_types=1);

namespace Surqlize\Model;

final class ModelValidator
{
    /**
     * @param class-string<Model> $modelClass
     * @param array<string, mixed> $data
     */
    public function validateData(string $modelClass, array $data): void
    {
        $metadata = ModelMetadata::for($modelClass);

        if ($metadata->schemaClass === null) {
            return;
        }

        $schema = new $metadata->schemaClass();
        $errors = $this->validateRules($schema->rules(), $data);

        if ($errors !== []) {
            throw new ValidationException($modelClass, $errors);
        }
    }

    public function validateModel(Model $model): void
    {
        $class = $model::class;

        $this->validateData($class, $model->toArray());
    }

    /**
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $data
     *
     * @return array<string, list<string>>
     */
    private function validateRules(array $rules, array $data): array
    {
        $errors = [];

        foreach ($rules as $property => $propertyRules) {
            $value = $data[$property] ?? null;
            $rulesForProperty = is_array($propertyRules) ? $propertyRules : [$propertyRules];

            foreach ($rulesForProperty as $rule) {
                $message = $this->validateRule((string) $property, $value, $data, $rule);

                if ($message !== null) {
                    $errors[(string) $property][] = $message;
                }
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateRule(string $property, mixed $value, array $data, mixed $rule): ?string
    {
        if ($rule instanceof \Closure || is_callable($rule)) {
            $result = $rule($value, $data, $property);

            return match (true) {
                $result === false => sprintf('Property "%s" is invalid.', $property),
                is_string($result) => $result,
                default => null,
            };
        }

        if (! is_string($rule)) {
            throw new \InvalidArgumentException(sprintf('Validation rule for property "%s" must be a string or callable.', $property));
        }

        return match ($rule) {
            'required' => $value === null ? sprintf('Property "%s" is required.', $property) : null,
            'string' => $value !== null && ! is_string($value) ? sprintf('Property "%s" must be a string.', $property) : null,
            'int', 'integer' => $value !== null && ! is_int($value) ? sprintf('Property "%s" must be an integer.', $property) : null,
            'float' => $value !== null && ! is_float($value) ? sprintf('Property "%s" must be a float.', $property) : null,
            'numeric' => $value !== null && ! is_int($value) && ! is_float($value) ? sprintf('Property "%s" must be numeric.', $property) : null,
            'bool', 'boolean' => $value !== null && ! is_bool($value) ? sprintf('Property "%s" must be a boolean.', $property) : null,
            'array' => $value !== null && ! is_array($value) ? sprintf('Property "%s" must be an array.', $property) : null,
            default => throw new \InvalidArgumentException(sprintf('Unsupported validation rule "%s" for property "%s".', $rule, $property)),
        };
    }
}
