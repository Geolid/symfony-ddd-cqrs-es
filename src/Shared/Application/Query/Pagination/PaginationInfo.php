<?php

declare(strict_types=1);

namespace Shared\Application\Query\Pagination;

final readonly class PaginationInfo
{
    public function __construct(
        public int $totalItems,
        public int $currentPage,
        public int $itemsPerPage,
        public int $lastPage,
    ) {
    }
}
