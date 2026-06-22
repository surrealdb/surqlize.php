<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use Surqlize\Query\Ast\WhereCondition;
use Surqlize\Query\Operator;

/** Field reference for schema-backed full-text search fields. */
final class SearchField extends Field
{
    public function matches(string $query): WhereCondition
    {
        return $this->condition(Operator::MATCHES, $query);
    }
}
