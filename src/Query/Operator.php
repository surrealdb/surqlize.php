<?php

declare(strict_types=1);

namespace Surqlize\Query;

enum Operator: string
{
    case EQUALS = '=';
    case NOT_EQUALS = '!=';
    case GREATER_THAN = '>';
    case GREATER_THAN_OR_EQUAL = '>=';
    case LESS_THAN = '<';
    case LESS_THAN_OR_EQUAL = '<=';
    case INCLUDES = 'INCLUDES';
    case CONTAINS = 'CONTAINS';
    case LIKE = 'LIKE';
}
