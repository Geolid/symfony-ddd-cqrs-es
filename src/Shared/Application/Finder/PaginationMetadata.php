<?php

declare(strict_types=1);

namespace Shared\Application\Finder;

final readonly class PaginationMetadata
{
    public function __construct(
        public int $totalItems,
        public int $currentPage,
        public int $itemsPerPage,
        public int $lastPage,
    ) {
    }

    /**
     * @template TResult of object
     *
     * @param PaginatorInterface<TResult> $paginator
     */
    public static function fromPaginator(PaginatorInterface $paginator): self
    {
        return new self(
            totalItems: $paginator->totalItems(),
            currentPage: $paginator->currentPage(),
            itemsPerPage: $paginator->itemsPerPage(),
            lastPage: $paginator->lastPage(),
        );
    }
}
