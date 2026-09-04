<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Infrastructure\Projection\Finder;

use AfterSales\Withdrawal\Application\Exception\OrderResultNotFoundException;
use AfterSales\Withdrawal\Application\Finder\Order\OrderFinderInterface;
use AfterSales\Withdrawal\Application\Finder\Order\OrderResult;
use AfterSales\Withdrawal\Infrastructure\Projection\Projector\DbalOrderProjector;
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
        $qb->select('order_id', 'customer_id', 'shipping_address', 'delivered_at')
            ->from(DbalOrderProjector::TABLE)
            ->orderBy('order_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return OrderResult::class;
    }
}
