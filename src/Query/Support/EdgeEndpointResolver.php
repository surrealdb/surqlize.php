<?php

declare(strict_types=1);

namespace Surqlize\Query\Support;

use Surqlize\Edge\EdgeMetadata;
use Surqlize\Model\ModelMetadata;
use Surqlize\Query\EdgeDirection;

/**
 * Resolves edge endpoint model classes and tables for in()/out() queries.
 */
final class EdgeEndpointResolver
{
    /**
     * @param class-string $edgeClass
     *
	 * @return class-string<\Surqlize\Model\Model>
     */
    public static function endpointClass(string $edgeClass, EdgeDirection $direction): string
    {
		$metadata = EdgeMetadata::for($edgeClass);

		return match ($direction) {
			EdgeDirection::In => $metadata->inClass,
			EdgeDirection::Out => $metadata->outClass,
		};
    }

    /**
     * @param class-string $edgeClass
     */
    public static function endpointTable(string $edgeClass, EdgeDirection $direction): string
    {
		return ModelMetadata::for(self::endpointClass($edgeClass, $direction))->tableName;
    }

    /**
     * @param class-string $edgeClass
     */
    public static function edgeTableName(string $edgeClass): string
    {
		return EdgeMetadata::for($edgeClass)->tableName;
    }
}
