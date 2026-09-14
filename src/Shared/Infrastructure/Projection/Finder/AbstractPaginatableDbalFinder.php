<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Projection\Finder;

use Shared\Application\Finder\PaginatorInterface;

/**
 * @template TResult of object
 *
 * @extends AbstractIterableDbalFinder<TResult>
 */
abstract class AbstractPaginatableDbalFinder extends AbstractIterableDbalFinder
{
    /**
     * @return PaginatorInterface<TResult>
     */
    public function paginate(int $page, int $itemsPerPage): PaginatorInterface
    {
        return new DbalPaginator($this->connection, $this->query(...), $this->hydrate(...), $page, $itemsPerPage);
    }
}
