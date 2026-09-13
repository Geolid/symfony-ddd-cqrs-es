<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;
use Shopping\Checkout\Application\Finder\Customer\CustomerFinderInterface;
use Shopping\Checkout\Application\Finder\Customer\CustomerResult;
use Shopping\Checkout\Infrastructure\Projection\Projector\DbalCustomerProjector;

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
        $qb->select('id', 'shipping_address', 'billing_address', 'erasure_status')
            ->from(DbalCustomerProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return CustomerResult::class;
    }
}
