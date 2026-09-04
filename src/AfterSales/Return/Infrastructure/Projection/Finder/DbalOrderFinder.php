<?php

declare(strict_types=1);

namespace AfterSales\Return\Infrastructure\Projection\Finder;

use AfterSales\Return\Application\Exception\OrderResultNotFoundException;
use AfterSales\Return\Application\Finder\Order\OrderFinderInterface;
use AfterSales\Return\Application\Finder\Order\OrderResult;
use AfterSales\Return\Infrastructure\Projection\Projector\DbalOrderProjector;
use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<OrderResult>
 */
final class DbalOrderFinder extends AbstractDbalFinder implements OrderFinderInterface
{
    public function ofId(string $orderId): OrderResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($orderId): void {
                $qb->andWhere('order_id = :orderId')->setParameter('orderId', $orderId);
            },
        )->one() ?? throw OrderResultNotFoundException::forId($orderId);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('order_id', 'buyer_id', 'shipping_address', 'delivered_at')
            ->from(DbalOrderProjector::TABLE)
            ->orderBy('order_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return OrderResult::class;
    }
}
