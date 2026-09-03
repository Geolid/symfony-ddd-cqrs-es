<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Application\Finder\PaginatorInterface;
use Webmozart\Assert\Assert;

/**
 * @template TResult of object
 *
 * @implements PaginatorInterface<TResult>
 */
final class DbalPaginator implements PaginatorInterface
{
    use DbalCountTrait;

    /**
     * @param \Closure(): QueryBuilder                $query
     * @param \Closure(array<string, mixed>): TResult $hydrate
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly \Closure $query,
        private readonly \Closure $hydrate,
        private readonly int $page = 1,
        private readonly int $itemsPerPage = 20,
    ) {
        Assert::positiveInteger($page);
        Assert::positiveInteger($itemsPerPage);
    }

    public function currentPage(): int
    {
        return $this->page;
    }

    public function itemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function lastPage(): int
    {
        return (int) (ceil($this->totalItems() / $this->itemsPerPage) ?: 1);
    }

    public function totalItems(): int
    {
        return $this->countTotalItems($this->connection, $this->query);
    }

    public function count(): int
    {
        $offset = ($this->page - 1) * $this->itemsPerPage;

        return max(0, min($this->itemsPerPage, $this->totalItems() - $offset));
    }

    public function getIterator(): \Traversable
    {
        $qb = ($this->query)();
        $qb->setFirstResult(($this->page - 1) * $this->itemsPerPage)
            ->setMaxResults($this->itemsPerPage);

        foreach ($qb->executeQuery()->iterateAssociative() as $row) {
            yield ($this->hydrate)($row);
        }
    }
}
