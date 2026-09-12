<?php

declare(strict_types=1);

namespace Crm\Customer\Infrastructure\Projection\Finder;

use Crm\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Crm\Customer\Application\Finder\Customer\CustomerResult;
use Crm\Customer\Infrastructure\Projection\Projector\DbalCustomerProjector;
use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<CustomerResult>
 */
final class DbalCustomerFinder extends AbstractDbalFinder implements CustomerFinderInterface
{
    public function ofIdOrNull(string $id): ?CustomerResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($id): void {
                $qb->andWhere('id = :id')->setParameter('id', $id);
            },
        )->one();
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'first_name', 'last_name', 'email', 'registered_at', 'shipping_address', 'billing_address', 'erasure_status')
            ->from(DbalCustomerProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return CustomerResult::class;
    }
}
