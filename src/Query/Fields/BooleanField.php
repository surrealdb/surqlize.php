<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use Surqlize\Query\Ast\WhereCondition;

/** Field reference for boolean SurrealDB fields. */
final class BooleanField extends Field
{
    public function isTrue(): WhereCondition
    {
        return $this->eq(true);
    }

    public function isFalse(): WhereCondition
    {
        return $this->eq(false);
    }
}
