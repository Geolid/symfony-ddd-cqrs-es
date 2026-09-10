<?php

declare(strict_types=1);

namespace Sales\Ordering\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Ordering\Application\Finder\Shopper\ShopperFinderInterface;
use Sales\Ordering\Application\Finder\Shopper\ShopperResult;
use Sales\Ordering\Infrastructure\Projection\Projector\DbalShopperProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<ShopperResult>
 */
final class DbalShopperFinder extends AbstractDbalFinder implements ShopperFinderInterface
{
    public function ofIdOrNull(string $shopperId): ?ShopperResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($shopperId): void {
                $qb->andWhere('shopper_id = :shopperId')->setParameter('shopperId', $shopperId);
            },
        )->one();
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('shopper_id', 'shipping_address', 'billing_address', 'erasure_requested')
            ->from(DbalShopperProjector::TABLE)
            ->orderBy('shopper_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return ShopperResult::class;
    }
}
