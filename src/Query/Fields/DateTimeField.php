<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use Surqlize\Query\Ast\WhereCondition;

/** Field reference for SurrealDB datetime fields. */
final class DateTimeField extends Field
{
    public function before(\DateTimeInterface $value): WhereCondition
    {
        return $this->lt($value);
    }

    public function after(\DateTimeInterface $value): WhereCondition
    {
        return $this->gt($value);
    }
}
