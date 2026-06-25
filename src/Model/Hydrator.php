<?php

declare(strict_types=1);

namespace Surqlize\Model;

use ReflectionClass;
use SurrealDB\SDK\Types\RecordId;
use Surqlize\Support\ClassString;

final class Hydrator
{
	/** @var array<class-string<Model>, array{instantiable: bool, has_required_constructor_parameters: bool}> */
	private static array $constructorCache = [];

	/**
	 * @param class-string<Model> $class
	 * @param array<string, mixed> $row
	 */
	public function hydrate(string $class, array $row): Model
	{
		$class = ClassString::model($class);
		$metadata = ModelMetadata::for($class);
		$data = [];

		foreach ($row as $key => $value) {
			if (!isset($metadata->propertyLookup[$key])) {
				continue;
			}

			$data[$key] = $this->castValue($metadata, $key, $value);
		}

		if (is_subclass_of($class, HydratesFromRow::class)) {
			$factory = \Closure::fromCallable([$class, 'fromSurqlizeRow']);
			$instance = $factory($data);

			if (!$instance instanceof $class) {
				throw new \LogicException(
					sprintf(
						'Row factory for "%s" must return an instance of that model.',
						$class,
					),
				);
			}

			return $instance;
		}

		$instance = $this->newInstance($class);

		foreach ($data as $key => $value) {
			$instance->{$key} = $value;
		}

		return $instance;
	}

	private function castValue(
		ModelMetadata $metadata,
		string $property,
		mixed $value,
	): mixed {
		try {
			if ($value === null) {
				return null;
			}

			if ($property === $metadata->idProperty) {
				return $this->castId($value);
			}

			if (isset($metadata->casts[$property])) {
				return $this->castNested($metadata->casts[$property], $value);
			}

			return $value;
		} catch (\Throwable $exception) {
			throw new \InvalidArgumentException(
				sprintf(
					'Cannot hydrate model "%s" property "%s": %s',
					$metadata->class,
					$property,
					$exception->getMessage(),
				),
				previous: $exception,
			);
		}
	}

	/**
	 * @param class-string<Model> $class
	 */
	private function newInstance(string $class): Model
	{
		$constructor = self::$constructorCache[$class] ??= $this->resolveConstructorCapabilities($class);

		if (!$constructor['instantiable']) {
			throw new \LogicException(
				sprintf('Model "%s" is not instantiable.', $class),
			);
		}

		if (!$constructor['has_required_constructor_parameters']) {
			/** @var Model $instance */
			$instance = new $class();

			return $instance;
		}

		throw new \LogicException(
			sprintf(
				'Model "%s" has a constructor with required parameters; implement %s to hydrate it safely.',
				$class,
				HydratesFromRow::class,
			),
		);
	}

	/**
	 * @param class-string<Model> $class
	 *
	 * @return array{instantiable: bool, has_required_constructor_parameters: bool}
	 */
	private function resolveConstructorCapabilities(string $class): array
	{
		$reflection = new ReflectionClass($class);
		$constructor = $reflection->getConstructor();

		return [
			'instantiable' => $reflection->isInstantiable(),
			'has_required_constructor_parameters' => $constructor !== null && $constructor->getNumberOfRequiredParameters() > 0,
		];
	}

	/**
	 * @return RecordId<string>
	 */
	private function castId(mixed $value): RecordId
	{
		if ($value instanceof RecordId) {
			return $value;
		}

		if (is_string($value)) {
			return RecordId::parse($value);
		}

		if (is_array($value) && isset($value["table"], $value["id"])) {
			return new RecordId($value["table"], $value["id"]);
		}

		if (is_array($value) && isset($value["tb"], $value["id"])) {
			return new RecordId($value["tb"], $value["id"]);
		}

		throw new \InvalidArgumentException(
			sprintf(
				'Cannot hydrate RecordId from value of type "%s".',
				get_debug_type($value),
			),
		);
	}

	/**
	 * @param class-string<Model> $castClass
	 *
	 * @return Model|RecordId<string>|null
	 */
	private function castNested(
		string $castClass,
		mixed $value,
	): Model|RecordId|null {
		if ($value === null) {
			return null;
		}

		if ($value instanceof Model) {
			return $value;
		}

		if ($value instanceof RecordId) {
			return $value;
		}

		if (is_string($value)) {
			return RecordId::parse($value);
		}

		if (!is_array($value)) {
			throw new \InvalidArgumentException(
				sprintf(
					'Cannot hydrate cast "%s" from value of type "%s".',
					$castClass,
					get_debug_type($value),
				),
			);
		}

		return $this->hydrate($castClass, $value);
	}
}
