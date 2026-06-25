<?php

declare(strict_types=1);

namespace Surqlize\Schema;

final class Schema
{
    public static function table(string $name): TableDefinition
    {
        return new TableDefinition($name);
    }

    public static function analyzer(string $name): AnalyzerDefinition
    {
        return new AnalyzerDefinition($name);
    }
}
