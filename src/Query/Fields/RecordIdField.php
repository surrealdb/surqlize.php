<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

use SurrealDB\SDK\Types\RecordId;
use Surqlize\Query\Ast\WhereCondition;
use Surqlize\Query\Operator;

final class RecordIdField extends Field
{
    public function __construct(
        string $path,
        private readonly string $table,
    ) {
        parent::__construct($path);
    }

    public function table(): string
    {
        return $this->table;
    }

    public function eq(mixed $value): WhereCondition
    {
        return $this->condition(Operator::EQUALS, $this->normalize($value));
    }

    public function notEq(mixed $value): WhereCondition
    {
        return $this->condition(Operator::NOT_EQUALS, $this->normalize($value));
    }

    public function record(RecordId $recordId): WhereCondition
    {
        return $this->condition(Operator::EQUALS, $this->assertTable($recordId));
    }

    private function normalize(mixed $value): RecordId
    {
        if ($value instanceof RecordId) {
            return $this->assertTable($value);
        }

        if (is_string($value) || is_int($value) || is_array($value) || is_object($value)) {
            return new RecordId($this->table, $value);
        }

        throw new \InvalidArgumentException(
            sprintf('Record id value must be a string, integer, array, object, or RecordId; %s given.', get_debug_type($value)),
        );
    }

    private function assertTable(RecordId $recordId): RecordId
    {
        if ($recordId->table !== $this->table) {
            throw new \InvalidArgumentException(
                sprintf('Expected record id for table "%s"; got "%s".', $this->table, $recordId->table),
            );
        }

        return $recordId;
    }
}
