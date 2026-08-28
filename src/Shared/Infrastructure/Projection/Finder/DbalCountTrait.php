<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Webmozart\Assert\Assert;

trait DbalCountTrait
{
    /** @var int<0, max>|null */
    private ?int $cachedTotal = null;

    /**
     * @param \Closure(): QueryBuilder $query
     *
     * @return int<0, max>
     */
    private function countTotalItems(Connection $connection, \Closure $query): int
    {
        if (null !== $this->cachedTotal) {
            return $this->cachedTotal;
        }

        $qb = $query();
        $qb->resetOrderBy()
            ->setFirstResult(0)
            ->setMaxResults(null);

        $result = $connection->executeQuery(
            \sprintf('SELECT COUNT(*) FROM (%s) AS total', $qb->getSQL()),
            $qb->getParameters(),
            $qb->getParameterTypes(),
        )->fetchOne();

        Assert::natural($result);

        return $this->cachedTotal = $result;
    }
}
