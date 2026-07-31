<?php

declare(strict_types=1);

namespace Sales\Customer\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Application\Finder\Customer\CustomerResult;
use Sales\Customer\Infrastructure\Persistence\Projection\Projector\DbalCustomerProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<CustomerResult>
 *
 * @phpstan-type Row array{id: string, email: string|null, registered_at: string, erased_at: string|null}
 */
final class DbalCustomerFinder extends AbstractDbalFinder implements CustomerFinderInterface
{
    public function withoutErased(): static
    {
        return $this->filter(static function (QueryBuilder $qb): void {
            $qb->andWhere('erased_at IS NULL');
        });
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'email', 'registered_at', 'erased_at')
            ->from(DbalCustomerProjector::TABLE)
            ->orderBy('registered_at', 'DESC')
            ->addOrderBy('id', 'DESC');
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): CustomerResult
    {
        return new CustomerResult(
            id: $row['id'],
            email: $row['email'],
            registeredAt: new \DateTimeImmutable($row['registered_at'], new \DateTimeZone('UTC')),
            erasedAt: null !== $row['erased_at'] ? new \DateTimeImmutable($row['erased_at'], new \DateTimeZone('UTC')) : null,
        );
    }
}
