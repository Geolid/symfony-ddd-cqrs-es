<?php

declare(strict_types=1);

namespace Shared\Application\Finder;

enum SortDirection: string
{
    case Ascending = 'ASC';
    case Descending = 'DESC';
}
