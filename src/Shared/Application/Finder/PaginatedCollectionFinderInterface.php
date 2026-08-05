<?php

declare(strict_types=1);

namespace Shared\Application\Finder;

/**
 * @template TResult of object
 *
 * @extends CollectionFinderInterface<TResult>
 */
interface PaginatedCollectionFinderInterface extends CollectionFinderInterface
{
    /**
     * @return PaginatorInterface<TResult>
     */
    public function paginate(int $page, int $itemsPerPage): PaginatorInterface;
}
