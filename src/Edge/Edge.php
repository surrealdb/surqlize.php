<?php

declare(strict_types=1);

namespace Surqlize\Edge;

use Surqlize\Model\Model;
use Surqlize\Query\Ast\GraphDirection;
use Surqlize\Query\EdgeDirection;
use Surqlize\Support\ClassString;
use SurrealDB\SDK\Types\RecordId;

/**
 * @method EdgeQuery in()
 * @method EdgeQuery out()
 */
abstract class Edge extends Model
{
    /** @var RecordId<string> */
    public RecordId $in;

    /** @var RecordId<string> */
    public RecordId $out;

    /**
     * Static graph SELECT factories: Edge::out(HasAddress::class), Edge::in(HasAddress::class).
     *
     * @param list<mixed> $arguments
     */
    public static function __callStatic(string $name, array $arguments): GraphSelectField
    {
        $edgeClass = $arguments[0] ?? throw new \InvalidArgumentException(
            sprintf('Edge::%s() requires an edge class name.', $name),
        );

        if (! is_string($edgeClass)) {
            throw new \InvalidArgumentException('Edge class name must be a string.');
        }

        $edgeClass = ClassString::edge($edgeClass);

        return match ($name) {
            'out' => GraphSelectField::fromEdge($edgeClass, GraphDirection::Out),
            'in' => GraphSelectField::fromEdge($edgeClass, GraphDirection::In),
            default => throw new \BadMethodCallException(
                sprintf('Undefined static method Edge::%s().', $name),
            ),
        };
    }

    /**
     * Instance edge traversal: $edge->in(), $edge->out().
     *
     * @param list<mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        /** @var class-string<static> $class */
        $class = static::class;

        return match ($name) {
            'in' => EdgeQuery::forDirection($class, EdgeDirection::In),
            'out' => EdgeQuery::forDirection($class, EdgeDirection::Out),
            default => throw new \BadMethodCallException(
                sprintf('Undefined method %s::%s()', static::class, $name),
            ),
        };
    }
}
