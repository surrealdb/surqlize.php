<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use Surqlize\Support\ClassString;

final class FieldSetRegistry
{
    /** @var array<string, class-string<FieldSet>> */
    private static array $fieldSets = [];

	/** @var array<string, class-string<FieldSet>|null> */
	private static array $guessedFieldSets = [];

    /**
     * @param class-string|string $modelClass
     * @param class-string<FieldSet> $fieldSetClass
     */
    public static function register(string $modelClass, string $fieldSetClass): void
    {
        $fieldSetClass = ClassString::fieldSet($fieldSetClass);
        self::$fieldSets[$modelClass] = $fieldSetClass;
		self::$guessedFieldSets[$modelClass] = $fieldSetClass;
    }

    /**
     * @param class-string|string $modelClass
     */
    public static function resolve(string $modelClass): FieldSet
    {
		$fieldSetClass = self::$fieldSets[$modelClass] ?? self::cachedGuessFieldSetClass($modelClass);

        if ($fieldSetClass !== null) {
            return new $fieldSetClass();
        }

        return new FieldSet($modelClass);
    }

    /**
     * @return array<string, class-string<FieldSet>>
     */
    public static function all(): array
    {
        return self::$fieldSets;
    }

    public static function clear(): void
    {
        self::$fieldSets = [];
		self::$guessedFieldSets = [];
    }

	/**
	 * @param class-string|string $modelClass
	 *
	 * @return class-string<FieldSet>|null
	 */
	private static function cachedGuessFieldSetClass(string $modelClass): ?string
	{
		if (! array_key_exists($modelClass, self::$guessedFieldSets)) {
			self::$guessedFieldSets[$modelClass] = self::guessFieldSetClass($modelClass);
		}

		return self::$guessedFieldSets[$modelClass];
	}

    /**
     * @param class-string|string $modelClass
     *
     * @return class-string<FieldSet>|null
     */
    private static function guessFieldSetClass(string $modelClass): ?string
    {
        $position = strrpos($modelClass, '\\');

        if ($position === false) {
            return null;
        }

        $namespace = substr($modelClass, 0, $position);
        $shortName = substr($modelClass, $position + 1);

        $candidates = [
            $namespace . '\\Fields\\' . $shortName . 'Fields',
            $modelClass . 'Fields',
        ];

        foreach ($candidates as $candidate) {
            if (class_exists($candidate) && is_a($candidate, FieldSet::class, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
