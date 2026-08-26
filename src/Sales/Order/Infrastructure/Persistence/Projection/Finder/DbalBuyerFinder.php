<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Order\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Order\Application\Finder\Buyer\BuyerResult;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalBuyerProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<BuyerResult>
 */
final class DbalBuyerFinder extends AbstractDbalFinder implements BuyerFinderInterface
{
    public function ofIdOrNull(string $customerId): ?BuyerResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($customerId): void {
                $qb->andWhere('customer_id = :customerId')->setParameter('customerId', $customerId);
            },
        )->one();
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('customer_id', 'shipping_address', 'billing_address')
            ->from(DbalBuyerProjector::TABLE)
            ->orderBy('customer_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return BuyerResult::class;
    }
}
