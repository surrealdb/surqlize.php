<?php

declare(strict_types=1);

namespace Surqlize\Model;

interface HydratesFromRow
{
    /** @param array<string, mixed> $row */
    public static function fromSurqlizeRow(array $row): Model;
}
