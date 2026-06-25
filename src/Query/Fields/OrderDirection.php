<?php

declare(strict_types=1);

namespace Surqlize\Query\Fields;

enum OrderDirection: string
{
    case Ascending = 'ASC';
    case Descending = 'DESC';
}
