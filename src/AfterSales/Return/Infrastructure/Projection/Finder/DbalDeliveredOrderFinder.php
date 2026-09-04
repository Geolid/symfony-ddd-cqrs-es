<?php

declare(strict_types=1);

namespace AfterSales\Return\Infrastructure\Projection\Finder;

use AfterSales\Return\Application\Exception\DeliveredOrderResultNotFoundException;
use AfterSales\Return\Application\Finder\DeliveredOrder\DeliveredOrderFinderInterface;
use AfterSales\Return\Application\Finder\DeliveredOrder\DeliveredOrderResult;
use AfterSales\Return\Infrastructure\Projection\Projector\DbalDeliveredOrderProjector;
use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<DeliveredOrderResult>
 */
final class DbalDeliveredOrderFinder extends AbstractDbalFinder implements DeliveredOrderFinderInterface
{
    public function ofId(string $orderId): DeliveredOrderResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($orderId): void {
                $qb->andWhere('order_id = :orderId')->setParameter('orderId', $orderId);
            },
        )->one() ?? throw DeliveredOrderResultNotFoundException::forId($orderId);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('order_id', 'buyer_id', 'shipping_address', 'delivered_at')
            ->from(DbalDeliveredOrderProjector::TABLE)
            ->orderBy('order_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return DeliveredOrderResult::class;
    }
}
