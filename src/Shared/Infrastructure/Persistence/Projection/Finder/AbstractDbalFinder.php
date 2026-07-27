<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Application\Finder\PaginatorInterface;

/**
 * @template TResult of object
 *
 * @implements \IteratorAggregate<int, TResult>
 */
abstract class AbstractDbalFinder implements \IteratorAggregate, \Countable
{
    use DbalCountTrait;

    private QueryBuilder $queryBuilder;

    public function __construct(protected readonly Connection $connection)
    {
        $this->queryBuilder = $this->connection->createQueryBuilder();
        $this->buildBaseQuery($this->queryBuilder);
    }

    protected function __clone()
    {
        $this->queryBuilder = clone $this->queryBuilder;
    }

    /**
     * @return PaginatorInterface<TResult>
     */
    public function paginate(int $page, int $itemsPerPage): PaginatorInterface
    {
        /** @var DbalPaginator<TResult> */
        return (new DbalPaginator($this->connection, $this->query(), fn (array $row) => $this->mapRow($row)))
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

    abstract protected function buildBaseQuery(QueryBuilder $qb): void;

    /**
     * @param array<string, mixed> $row
     *
     * @return TResult
     */
    abstract protected function mapRow(array $row): object;

    /**
     * @param callable(QueryBuilder): void $filter
     */
    protected function filter(callable $filter): static
    {
        $clone = clone $this;
        $filter($clone->queryBuilder);

        return $clone;
    }

    protected function query(): QueryBuilder
    {
        return clone $this->queryBuilder;
    }
}
