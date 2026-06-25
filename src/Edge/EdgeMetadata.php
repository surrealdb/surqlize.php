<?php

declare(strict_types=1);

namespace Surqlize\Edge;

use ReflectionClass;
use Surqlize\Attributes\Edge as EdgeAttribute;
use Surqlize\Model\Model;
use Surqlize\Query\Compiler\Identifier;
use Surqlize\Support\ClassString;

final class EdgeMetadata
{
    /** @var array<class-string, self> */
    private static array $cache = [];

    /**
     * @param class-string $class
     * @param class-string<Model> $inClass
     * @param class-string<Model> $outClass
     */
    private function __construct(
        public readonly string $class,
        public readonly string $tableName,
        public readonly string $inClass,
        public readonly string $outClass,
    ) {}

    /**
     * @param class-string $class
     */
    public static function for(string $class): self
    {
        $class = ClassString::edge($class);

        return self::$cache[$class] ??= self::resolve($class);
    }

    /**
     * @param class-string $class
     */
    private static function resolve(string $class): self
    {
        $reflection = new ReflectionClass($class);
        $attributes = $reflection->getAttributes(EdgeAttribute::class);

        if ($attributes === []) {
            throw new \InvalidArgumentException(
                sprintf('Class %s has no #[Edge] attribute.', $class),
            );
        }

        if (count($attributes) > 1) {
            throw new \InvalidArgumentException(
                sprintf('Class %s defines multiple #[Edge] attributes.', $class),
            );
        }

        /** @var EdgeAttribute $edge */
        $edge = $attributes[0]->newInstance();

        $inClass = ClassString::model($edge->in, sprintf('Edge %s in endpoint', $class));
        $outClass = ClassString::model($edge->out, sprintf('Edge %s out endpoint', $class));

        return new self(
            class: $class,
            tableName: Identifier::table($edge->name),
            inClass: $inClass,
            outClass: $outClass,
        );
    }
}
