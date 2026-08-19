<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Application\Finder\PaginatorInterface;

/**
 * @template TResult of object
 *
 * @extends AbstractDbalFinder<TResult>
 *
 * @implements \IteratorAggregate<int, TResult>
 */
abstract class AbstractDbalCollectionFinder extends AbstractDbalFinder implements \IteratorAggregate, \Countable
{
    use DbalCountTrait;

    protected function __clone()
    {
        parent::__clone();
        $this->cachedTotal = null;
    }

    /**
     * @return PaginatorInterface<TResult>
     */
    public function paginate(int $page, int $itemsPerPage): PaginatorInterface
    {
        /** @var DbalPaginator<TResult> */
        return new DbalPaginator($this->connection, $this->query(), fn (array $row): object => $this->mapRow($row))
            ->withPagination($page, $itemsPerPage);
    }

    /**
     * @return \Iterator<int, TResult>
     */
    public function getIterator(): \Iterator
    {
        $result = $this->query()->executeQuery();

        foreach ($result->iterateAssociative() as $row) {
            yield $this->mapRow($row);
        }
    }

    public function count(): int
    {
        return $this->countTotalItems($this->connection, $this->query());
    }

    /**
     * @param callable(TResult): string $keyExtractor
     *
     * @return \Traversable<string, TResult>
     */
    public function indexedBy(callable $keyExtractor): \Traversable
    {
        foreach ($this as $item) {
            yield $keyExtractor($item) => $item;
        }
    }

    /**
     * @param callable(QueryBuilder): void $filter
     */
    protected function filter(callable $filter): static
    {
        $clone = clone $this;
        $filter($clone->queryBuilder);

        return $clone;
    }
}
