<?php

declare(strict_types=1);

namespace Surqlize\Relate;

enum Time: string
{
    case Seconds = 's';
    case Minutes = 'm';
    case Hours = 'h';

    public function format(int $amount): string
    {
        return $amount . $this->value;
    }
}
