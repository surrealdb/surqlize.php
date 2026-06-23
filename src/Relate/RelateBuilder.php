<?php

declare(strict_types=1);

namespace Surqlize\Relate;

use Surqlize\Connection\ConnectionManager;
use Surqlize\Edge\EdgeMetadata;
use Surqlize\Model\Model;
use Surqlize\Query\Concerns\CompilesQueries;
use Surqlize\Query\Compiler\Identifier;
use Surqlize\Support\ClassString;
use SurrealDB\SDK\Contracts\QueryExecutor;
use SurrealDB\SDK\Query\RelateQuery;
use SurrealDB\SDK\Types\RecordId;
use SurrealDB\SDK\Types\Table;

class RelateBuilder implements CompilesQueries
{
    /** @var class-string<Model> */
    private readonly string $sourceClass;

    private ?Model $from = null;

    private ?Model $to = null;

    /** @var class-string<Model>|null */
    private ?string $edgeClass = null;

    /** @var array<string, mixed> */
    private array $content = [];

    private ?string $timeout = null;

    public function __construct(
        string $sourceClass,
        Model $from,
        private readonly ?QueryExecutor $executor = null,
    ) {
        $sourceClass = ClassString::model($sourceClass, 'Relate source class');

        if ($from::class !== $sourceClass) {
            throw new \InvalidArgumentException(
                sprintf(
                    'relate() must be called on %s with an instance of %s, got %s.',
                    $sourceClass,
                    $sourceClass,
                    $from::class,
                ),
            );
        }

        $this->sourceClass = $sourceClass;
        $this->from = $from;
    }

    /**
     * @param class-string<Model> $edgeClass
     */
    public function edge(string $edgeClass): self
    {
        $edgeClass = ClassString::edge($edgeClass);
        $this->edgeClass = $edgeClass;
        $metadata = EdgeMetadata::for($edgeClass);
        $from = $this->from;

        if ($from === null) {
            throw new \LogicException(sprintf('relate() source model is missing while configuring edge "%s".', $edgeClass));
        }

        if ($from::class !== $metadata->inClass) {
            throw new \InvalidArgumentException(
                sprintf(
                    'relate() source must be the in endpoint %s for edge %s, got %s.',
                    $metadata->inClass,
                    $edgeClass,
                    $from::class,
                ),
            );
        }

        return $this;
    }

    public function with(Model $model): self
    {
        $edgeClass = $this->requireEdgeClass();
        $metadata = EdgeMetadata::for($edgeClass);

        if ($model::class !== $metadata->outClass) {
            throw new \InvalidArgumentException(
                sprintf(
                    'with() expects the out endpoint %s for edge %s, got %s.',
                    $metadata->outClass,
                    $edgeClass,
                    $model::class,
                ),
            );
        }

        $this->to = $model;

        return $this;
    }

    /** @param array<string, mixed> $data */
    public function content(array $data): self
    {
        foreach ($data as $key => $value) {
            $this->content[$key] = $value;
        }

        return $this;
    }

    public function set(string $key, mixed $value): self
    {
        $this->content[$key] = $value;

        return $this;
    }

    public function timeout(int $amount, Time $unit): self
    {
        $this->timeout = $unit->format($amount);

        return $this;
    }

    public function compile(): string
    {
        [$from, $edgeTable, $to] = $this->resolvedEndpoints();

        $sql = sprintf(
            'RELATE %s->%s->%s',
            $from->escaped,
            Identifier::table($edgeTable, 'RELATE edge table'),
            $to->escaped,
        );

        if ($this->content !== []) {
            $sql .= ' CONTENT ' . $this->compileContent($this->content);
        }

        if ($this->timeout !== null) {
            $sql .= ' TIMEOUT ' . $this->timeout;
        }

        return $sql;
    }

    public function execute(): mixed
    {
        return $this->toSdkQuery()->execute();
    }

    /**
     * @return RelateQuery<mixed>
     */
    public function toSdkQuery(): RelateQuery
    {
        [$from, $edgeTable, $to] = $this->resolvedEndpoints();
        $executor = $this->executor ?? ConnectionManager::get();

        $query = new RelateQuery($executor, $from, new Table(Identifier::table($edgeTable, 'RELATE edge table')), $to);

        if ($this->content !== []) {
            $query->content($this->content);
        }

        if ($this->timeout !== null) {
            $query->timeout($this->timeout);
        }

        return $query;
    }

    /**
     * @return array{0: RecordId<string>, 1: string, 2: RecordId<string>}
     */
    private function resolvedEndpoints(): array
    {
        $edgeClass = $this->requireEdgeClass();
        $metadata = EdgeMetadata::for($edgeClass);

        if ($this->from === null || $this->to === null) {
            throw new \LogicException(
                sprintf(
                    'RELATE requires both endpoints for edge %s (%s -> %s); current source is %s and target is %s.',
                    $edgeClass,
                    $metadata->inClass,
                    $metadata->outClass,
                    $this->from !== null ? $this->from::class : 'missing',
                    $this->to !== null ? $this->to::class : 'missing',
                ),
            );
        }

        return [
            $this->recordId($this->from, $metadata->inClass),
            $metadata->tableName,
            $this->recordId($this->to, $metadata->outClass),
        ];
    }

    /**
     * @param class-string<Model> $expectedClass
     *
     * @return RecordId<string>
     */
    private function recordId(Model $model, string $expectedClass): RecordId
    {
        if ($model::class !== $expectedClass) {
            throw new \InvalidArgumentException(
                sprintf('Expected model %s, got %s.', $expectedClass, $model::class),
            );
        }

        $metadata = \Surqlize\Model\ModelMetadata::for($expectedClass);
        $idProperty = $metadata->idProperty;

        if ($idProperty === null) {
            throw new \LogicException(sprintf('Model %s has no #[Id] property required for RELATE endpoints.', $expectedClass));
        }

        $id = $model->{$idProperty};

        if (! $id instanceof RecordId) {
            throw new \LogicException(
                sprintf(
                    'Model %s id property "%s" must be a RecordId before relating; got %s.',
                    $expectedClass,
                    $idProperty,
                    get_debug_type($id),
                ),
            );
        }

        return $id;
    }

    /** @param array<string, mixed> $data */
    private function compileContent(array $data): string
    {
        $content = '';

        foreach ($data as $key => $value) {
            if ($content !== '') {
                $content .= ', ';
            }

            $content .= sprintf('%s: %s', Identifier::field($key, 'RELATE content key'), $this->compileContentValue($value));
        }

        return '{ ' . $content . ' }';
    }

    private function compileContentValue(mixed $value): string
    {
        if ($value === null) {
            return 'NONE';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return '"' . str_replace('"', '\\"', $value) . '"';
        }

        if ($value instanceof \DateTimeInterface) {
            return "d'" . $value->format('Y-m-d\TH:i:s') . "'";
        }

        throw new \InvalidArgumentException(
            sprintf('Cannot compile content value of type %s.', get_debug_type($value)),
        );
    }

    /** @return class-string<Model> */
    private function requireEdgeClass(): string
    {
        if ($this->edgeClass === null) {
            throw new \LogicException('Edge class must be set before resolving RELATE endpoints. Call edge(YourEdge::class) before with(), compile(), or execute().');
        }

        return $this->edgeClass;
    }
}
