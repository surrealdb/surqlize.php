<?php

declare(strict_types=1);

namespace Surqlize\Connection;

use SurrealDB\SDK\Contracts\QueryExecutor;
use SurrealDB\SDK\Query\BoundQuery;
use Surqlize\Query\Concerns\CompilesQueries;

final class Transaction implements QueryExecutor
{
    /** @var list<BoundQuery> */
    private array $queries = [];

    private bool $closed = false;

    public function __construct(
        private readonly QueryExecutor $executor,
    ) {}

    public function query(BoundQuery $query): mixed
    {
        $this->assertOpen();
        $this->queries[] = $query;

        return [];
    }

    public function add(BoundQuery|CompilesQueries|string $query): self
    {
        $this->assertOpen();

        if ($query instanceof BoundQuery) {
            $this->queries[] = $query;

            return $this;
        }

        $this->queries[] = new BoundQuery((string) ($query instanceof CompilesQueries ? $query->compile() : $query));

        return $this;
    }

    public function commit(): mixed
    {
        $this->assertOpen();
        $this->closed = true;

        if ($this->queries === []) {
            return null;
        }

        $query = new BoundQuery('BEGIN TRANSACTION; ');

        foreach ($this->queries as $index => $item) {
            $this->appendTransactionQuery($query, $item, $index);
            $query->append('; ');
        }

        $query->append('COMMIT TRANSACTION;');

        try {
            return $this->executor->query($query);
        } catch (\Throwable $exception) {
            $this->executor->query(new BoundQuery('CANCEL TRANSACTION;'));

            throw $exception;
        }
    }

    public function rollback(): void
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;
        $this->queries = [];
    }

    private function appendTransactionQuery(BoundQuery $target, BoundQuery $source, int $index): void
    {
        $sql = $source->query;
        $bindings = [];

        foreach ($source->bindings as $key => $value) {
            $renamed = 'tx_' . $index . '_' . $key;
            $sql = str_replace('$' . $key, '$' . $renamed, $sql);
            $bindings[$renamed] = $value;
        }

        $target->append($sql, $bindings);
    }

    private function assertOpen(): void
    {
        if ($this->closed) {
            throw new \LogicException('Transaction is already closed.');
        }
    }
}
