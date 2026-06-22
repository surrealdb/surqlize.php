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
    case CONTAINS_ALL = 'CONTAINSALL';
    case CONTAINS_ANY = 'CONTAINSANY';
    case CONTAINS_NONE = 'CONTAINSNONE';
    case INSIDE = 'INSIDE';
    case OUTSIDE = 'OUTSIDE';
    case INTERSECTS = 'INTERSECTS';
    case LIKE = 'LIKE';
    case MATCHES = '@@';
}
