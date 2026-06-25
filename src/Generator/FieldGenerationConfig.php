<?php

declare(strict_types=1);

namespace Surqlize\Generator;

use Surqlize\Model\Model;
use Surqlize\Support\ClassString;

final readonly class FieldGenerationConfig
{
    /**
     * @param list<class-string<Model>> $models
     */
    public function __construct(
        public array $models,
        public string $fieldsNamespace,
        public string $fieldsPath,
    ) {}

    /**
     * @param array{models?: list<class-string<Model>>, fields_namespace?: string, fields_path?: string} $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            models: self::models($config['models'] ?? []),
            fieldsNamespace: self::string($config, 'fields_namespace'),
            fieldsPath: self::string($config, 'fields_path'),
        );
    }

    /**
     * @return list<class-string<Model>>
     */
    private static function models(mixed $models): array
    {
        if (! is_array($models)) {
            throw new \InvalidArgumentException(sprintf('Surqlize field generation config "models" must be a list of model class strings; %s given.', get_debug_type($models)));
        }

        $validated = [];

        foreach (array_values($models) as $index => $modelClass) {
            if (! is_string($modelClass)) {
                throw new \InvalidArgumentException(sprintf('Surqlize field generation config "models" item at index %d must be a model class string; %s given.', $index, get_debug_type($modelClass)));
            }

            $validated[] = ClassString::model($modelClass, sprintf('Surqlize field generation config "models" item at index %d', $index));
        }

        return $validated;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function string(array $config, string $key): string
    {
        if (! array_key_exists($key, $config)) {
            throw new \InvalidArgumentException(sprintf('Missing Surqlize field generation config value "%s".', $key));
        }

        if (! is_string($config[$key]) || $config[$key] === '') {
            throw new \InvalidArgumentException(sprintf('Surqlize field generation config "%s" must be a non-empty string; %s given.', $key, get_debug_type($config[$key])));
        }

        return $config[$key];
    }
}
