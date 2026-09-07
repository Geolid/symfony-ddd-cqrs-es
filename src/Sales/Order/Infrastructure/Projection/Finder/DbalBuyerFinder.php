<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Order\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Order\Application\Finder\Buyer\BuyerResult;
use Sales\Order\Infrastructure\Projection\Projector\DbalBuyerProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<BuyerResult>
 */
final class DbalBuyerFinder extends AbstractDbalFinder implements BuyerFinderInterface
{
    public function ofIdOrNull(string $buyerId): ?BuyerResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($buyerId): void {
                $qb->andWhere('buyer_id = :buyerId')->setParameter('buyerId', $buyerId);
            },
        )->one();
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('buyer_id', 'shipping_address', 'erasure_pending')
            ->from(DbalBuyerProjector::TABLE)
            ->orderBy('buyer_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return BuyerResult::class;
    }
}
