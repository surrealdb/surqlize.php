<?php

declare(strict_types=1);

namespace Surqlize\Connection;

use RuntimeException;
use SurrealDB\SDK\Contracts\QueryExecutor;

/**
 * v1 singleton holder for the SDK {@see Surreal} instance.
 */
final class ConnectionManager
{
    private static ?QueryExecutor $executor = null;

    public static function set(QueryExecutor $executor): void
    {
        self::$executor = $executor;
    }

    public static function get(): QueryExecutor
    {
        if (self::$executor === null) {
            throw new RuntimeException(
                'No Surreal connection configured. Call ConnectionManager::set() during bootstrap.',
            );
        }

        return self::$executor;
    }

    public static function reset(): void
    {
        self::$executor = null;
    }

    public static function transaction(\Closure $callback, ?QueryExecutor $executor = null): mixed
    {
        $transaction = new Transaction($executor ?? self::get());

        try {
            $result = $callback($transaction);
            $transaction->commit();

            return $result;
        } catch (\Throwable $exception) {
            $transaction->rollback();

            throw $exception;
        }
    }
}
