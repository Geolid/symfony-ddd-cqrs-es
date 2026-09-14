<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Patchlevel\Hydrator\Hydrator;
use Shared\Application\Finder\PaginatorInterface;
use Shared\Application\Finder\SortDirection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Webmozart\Assert\Assert;

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

    /** @var array<string, SortDirection> */
    private array $sorts = [];

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
        return new DbalPaginator($this->connection, $this->query(...), $this->hydrate(...), $page, $itemsPerPage);
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
     * Fallback sort applied to all queries.
     * The combined columns must guarantee row uniqueness for deterministic pagination.
     *
     * @return non-empty-array<string, SortDirection>
     */
    abstract protected function defaultSort(): array;

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

    /**
     * @param array<string, SortDirection> $sorts
     */
    protected function sortBy(array $sorts): static
    {
        $clone = clone $this;
        $clone->sorts = $sorts;

        return $clone;
    }

    private function query(): QueryBuilder
    {
        $qb = $this->connection->createQueryBuilder();
        $this->buildBaseQuery($qb);

        foreach ($this->filters as $filter) {
            $filter($qb);
        }

        $qb->resetOrderBy();

        $default = $this->defaultSort();
        Assert::notEmpty($default, \sprintf('%s::defaultSort() must return at least one column.', static::class));

        $sorts = $this->sorts + $default;

        foreach ($sorts as $column => $direction) {
            $qb->addOrderBy($column, $this->direction($direction));
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

    private function direction(SortDirection $direction): string
    {
        return match ($direction) {
            SortDirection::Ascending => 'ASC',
            SortDirection::Descending => 'DESC',
        };
    }
}
