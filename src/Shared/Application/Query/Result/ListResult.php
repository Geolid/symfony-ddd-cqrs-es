<?php

declare(strict_types=1);

namespace Shared\Application\Query\Result;

use Shared\Application\Query\Pagination\PaginationInfo;

/**
 * @template TResult of object
 *
 * @implements \IteratorAggregate<int, TResult>
 */
final readonly class ListResult implements \IteratorAggregate, \Countable, ResultInterface
{
    /**
     * @param list<TResult> $items
     */
    public function __construct(
        public array $items,
        public PaginationInfo $pagination,
    ) {
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return \count($this->items);
    }
}
