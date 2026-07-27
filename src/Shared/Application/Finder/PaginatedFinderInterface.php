<?php

declare(strict_types=1);

namespace Shared\Application\Finder;

/**
 * @template TResult of object
 *
 * @extends FinderInterface<TResult>
 */
interface PaginatedFinderInterface extends FinderInterface
{
    /**
     * @return PaginatorInterface<TResult>
     */
    public function paginate(int $page, int $itemsPerPage): PaginatorInterface;
}
