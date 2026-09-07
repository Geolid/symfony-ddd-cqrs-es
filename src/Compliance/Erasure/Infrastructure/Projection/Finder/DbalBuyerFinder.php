<?php

declare(strict_types=1);

namespace Compliance\Erasure\Infrastructure\Projection\Finder;

use Compliance\Erasure\Application\Finder\Buyer\BuyerFinderInterface;
use Compliance\Erasure\Application\Finder\Buyer\BuyerResult;
use Compliance\Erasure\Infrastructure\Projection\Projector\DbalBuyerProjector;
use Doctrine\DBAL\Query\QueryBuilder;
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
        $qb->select('buyer_id', 'identity_id')
            ->from(DbalBuyerProjector::TABLE)
            ->orderBy('buyer_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return BuyerResult::class;
    }
}
