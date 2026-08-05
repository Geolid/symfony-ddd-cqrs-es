<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * @template TResult of object
 */
abstract class AbstractDbalFinder
{
    protected QueryBuilder $queryBuilder;

    public function __construct(protected readonly Connection $connection)
    {
        $this->queryBuilder = $this->connection->createQueryBuilder();
        $this->buildBaseQuery($this->queryBuilder);
    }

    protected function __clone()
    {
        $this->queryBuilder = clone $this->queryBuilder;
    }

    abstract protected function buildBaseQuery(QueryBuilder $qb): void;

    /**
     * @param array<string, mixed> $row
     *
     * @return TResult
     */
    abstract protected function mapRow(array $row): object;

    protected function query(): QueryBuilder
    {
        return clone $this->queryBuilder;
    }
}
