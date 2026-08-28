<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Patchlevel\Hydrator\Hydrator;
use Shared\Application\Finder\PaginatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @template TResult of object
 *
 * @implements \IteratorAggregate<int, TResult>
 */
abstract class AbstractDbalFinder implements \IteratorAggregate, \Countable
{
    use DbalCountTrait;

    /** @var list<callable(QueryBuilder): void> */
    private array $filters = [];

    public function __construct(
        protected readonly Connection $connection,
        #[Autowire(service: 'shared.hydration.result_hydrator')]
        private readonly Hydrator $hydrator,
    ) {
    }

    protected function __clone(): void
    {
        $this->cachedTotal = null;
    }

    /**
     * @return PaginatorInterface<TResult>
     */
    public function paginate(int $page, int $itemsPerPage): PaginatorInterface
    {
        /** @var DbalPaginator<TResult> */
        return new DbalPaginator($this->connection, $this->query(...), $this->hydrate(...))
            ->withPagination($page, $itemsPerPage);
    }

    /**
     * @return \Iterator<int, TResult>
     */
    public function getIterator(): \Iterator
    {
        $result = $this->query()->executeQuery();

        foreach ($result->iterateAssociative() as $row) {
            yield $this->hydrate($row);
        }
    }

    public function count(): int
    {
        return $this->countTotalItems($this->connection, $this->query(...));
    }

    /**
     * @param callable(TResult): string $keyExtractor
     *
     * @return \Traversable<string, TResult>
     */
    public function indexBy(callable $keyExtractor): \Traversable
    {
        foreach ($this as $item) {
            yield $keyExtractor($item) => $item;
        }
    }

    abstract protected function buildBaseQuery(QueryBuilder $qb): void;

    /**
     * @return class-string<TResult>
     */
    abstract protected function resultClass(): string;

    /**
     * @return TResult|null
     */
    protected function one(): ?object
    {
        $row = $this->query()->setMaxResults(1)->executeQuery()->fetchAssociative();

        return false !== $row ? $this->hydrate($row) : null;
    }

    /**
     * @param callable(QueryBuilder): void $filter
     */
    protected function filter(callable $filter): static
    {
        $clone = clone $this;
        $clone->filters[] = $filter;

        return $clone;
    }

    private function query(): QueryBuilder
    {
        $qb = $this->connection->createQueryBuilder();
        $this->buildBaseQuery($qb);

        foreach ($this->filters as $filter) {
            $filter($qb);
        }

        return $qb;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return TResult
     */
    private function hydrate(array $row): object
    {
        return $this->hydrator->hydrate($this->resultClass(), $row);
    }
}
