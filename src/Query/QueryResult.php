<?php

declare(strict_types=1);

namespace Surqlize\Query;

final class QueryResult
{
    /**
     * The SDK returns one item per SurrealQL statement. Surqlize model queries
     * execute one statement, so unwrap that statement result when present.
     *
     * @param list<mixed> $result
     *
     * @return list<mixed>
     */
    public static function singleStatement(array $result): array
    {
        if (count($result) !== 1) {
            return $result;
        }

        $statement = $result[0];

        if (! is_array($statement) || ! array_is_list($statement)) {
            return $result;
        }

        return $statement;
    }
}
