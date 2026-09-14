<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Application\Finder\SortDirection;
use Webmozart\Assert\Assert;

/**
 * @template TResult of object
 *
 * @extends AbstractDbalFinder<TResult>
 *
 * @implements \IteratorAggregate<int, TResult>
 */
abstract class AbstractIterableDbalFinder extends AbstractDbalFinder implements \IteratorAggregate, \Countable
{
    /** @var array<string, SortDirection> */
    private array $sorts = [];

    /** @var int<0, max>|null */
    private ?int $cachedTotal = null;

    protected function __clone(): void
    {
        $this->cachedTotal = null;
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
        if (null !== $this->cachedTotal) {
            return $this->cachedTotal;
        }

        $qb = $this->query()
            ->resetOrderBy()
            ->setFirstResult(0)
            ->setMaxResults(null);

        $result = $this->connection->executeQuery(
            \sprintf('SELECT COUNT(*) FROM (%s) AS total', $qb->getSQL()),
            $qb->getParameters(),
            $qb->getParameterTypes(),
        )->fetchOne();

        Assert::natural($result);

        return $this->cachedTotal = $result;
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

    /**
     * Fallback sort applied to all queries.
     * The combined columns must guarantee row uniqueness for deterministic pagination.
     *
     * @return non-empty-array<string, SortDirection>
     */
    abstract protected function defaultSort(): array;

    /**
     * @param array<string, SortDirection> $sorts
     */
    protected function sortBy(array $sorts): static
    {
        $clone = clone $this;
        $clone->sorts = $sorts;

        return $clone;
    }

    protected function query(): QueryBuilder
    {
        $qb = parent::query();
        $qb->resetOrderBy();

        $default = $this->defaultSort();
        Assert::notEmpty($default, \sprintf('%s::defaultSort() must return at least one column.', static::class));

        $sorts = $this->sorts + $default;

        foreach ($sorts as $column => $direction) {
            $qb->addOrderBy($column, $this->direction($direction));
        }

        return $qb;
    }

    private function direction(SortDirection $direction): string
    {
        return match ($direction) {
            SortDirection::Ascending => 'ASC',
            SortDirection::Descending => 'DESC',
        };
    }
}
