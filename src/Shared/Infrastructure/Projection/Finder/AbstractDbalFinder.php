<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Patchlevel\Hydrator\Hydrator;
use Shared\Infrastructure\Projection\Finder\Exception\NonUniqueResultException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @template TResult of object
 */
abstract class AbstractDbalFinder
{
    /** @var list<callable(QueryBuilder): void> */
    private array $filters = [];

    public function __construct(
        protected readonly Connection $connection,
        #[Autowire(service: 'shared.hydration.hydrator')]
        private readonly Hydrator $hydrator,
    ) {
    }

    abstract protected function configureBaseQuery(QueryBuilder $qb): void;

    /**
     * @return class-string<TResult>
     */
    abstract protected function resultClass(): string;

    /**
     * @return TResult|null
     */
    protected function one(): ?object
    {
        $rows = $this->query()->setMaxResults(2)->executeQuery()->fetchAllAssociative();

        if (\count($rows) > 1) {
            throw NonUniqueResultException::forClass($this->resultClass());
        }

        return [] !== $rows ? $this->hydrate($rows[0]) : null;
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

    protected function query(): QueryBuilder
    {
        $qb = $this->connection->createQueryBuilder();
        $this->configureBaseQuery($qb);

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
    protected function hydrate(array $row): object
    {
        return $this->hydrator->hydrate($this->resultClass(), $row);
    }
}
