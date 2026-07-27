<?php

declare(strict_types=1);

namespace Shared\Application\Finder;

/**
 * @template TResult of object
 *
 * @extends \IteratorAggregate<array-key, TResult>
 */
interface PaginatorInterface extends \IteratorAggregate, \Countable
{
    public function currentPage(): int;

    public function itemsPerPage(): int;

    public function lastPage(): int;

    public function totalItems(): int;
}
