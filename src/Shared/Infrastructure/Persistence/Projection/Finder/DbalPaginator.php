<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Persistence\Projection\Finder;

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

    public function __construct(
        private readonly Connection $connection,
        private readonly QueryBuilder $queryBuilder,
        private readonly \Closure $hydrator,
        private int $page = 1,
        private int $itemsPerPage = 20,
    ) {
    }

    /**
     * @return self<TResult>
     */
    public function withPagination(int $page, int $itemsPerPage): self
    {
        Assert::positiveInteger($page);
        Assert::positiveInteger($itemsPerPage);

        $clone = clone $this;
        $clone->page = $page;
        $clone->itemsPerPage = $itemsPerPage;

        return $clone;
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
        return $this->countTotalItems($this->connection, $this->queryBuilder);
    }

    public function count(): int
    {
        $offset = ($this->page - 1) * $this->itemsPerPage;

        return max(0, min($this->itemsPerPage, $this->totalItems() - $offset));
    }

    public function getIterator(): \Traversable
    {
        $qb = clone $this->queryBuilder;
        $qb->setFirstResult(($this->page - 1) * $this->itemsPerPage)
            ->setMaxResults($this->itemsPerPage);

        foreach ($qb->executeQuery()->iterateAssociative() as $row) {
            yield ($this->hydrator)($row);
        }
    }
}
