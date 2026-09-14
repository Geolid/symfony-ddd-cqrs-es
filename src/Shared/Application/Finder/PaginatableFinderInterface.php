<?php

declare(strict_types=1);

namespace Shared\Application\Finder;

/**
 * @template TResult of object
 *
 * @extends IterableFinderInterface<TResult>
 */
interface PaginatableFinderInterface extends IterableFinderInterface
{
    /**
     * @return PaginatorInterface<TResult>
     */
    public function paginate(int $page, int $itemsPerPage): PaginatorInterface;
}
