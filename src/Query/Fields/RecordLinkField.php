<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use SurrealDB\SDK\Types\RecordId;
use Surqlize\Query\Ast\WhereCondition;
use Surqlize\Query\Operator;

/** Field reference for non-primary record links. */
final class RecordLinkField extends Field
{
    /**
     * @param RecordId<string> $recordId
     */
    public function record(RecordId $recordId): WhereCondition
    {
        return $this->condition(Operator::EQUALS, $recordId);
    }
}
