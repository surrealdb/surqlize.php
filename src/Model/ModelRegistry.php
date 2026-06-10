<?php

declare(strict_types=1);

namespace Surqlize\Model;

final class ModelRegistry
{
    /** @var array<string, class-string<Model>> */
    private static array $byTable = [];

    public static function register(ModelMetadata $metadata): void
    {
        if (isset(self::$byTable[$metadata->tableName])
            && self::$byTable[$metadata->tableName] !== $metadata->class) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Table "%s" is already registered to "%s"; cannot register "%s".',
                    $metadata->tableName,
                    self::$byTable[$metadata->tableName],
                    $metadata->class,
                ),
            );
        }

        self::$byTable[$metadata->tableName] = $metadata->class;
    }

    /**
     * @return class-string<Model>|null
     */
    public static function resolve(string $table): ?string
    {
        return self::$byTable[$table] ?? null;
    }

    /**
     * @return array<string, class-string<Model>>
     */
    public static function all(): array
    {
        return self::$byTable;
    }

    public static function clear(): void
    {
        self::$byTable = [];
    }
}
