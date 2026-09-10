<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Fulfilment\Shipping\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Fulfilment\Shipping\Application\Finder\OrderPayment\OrderPaymentResult;
use Fulfilment\Shipping\Infrastructure\Projection\Projector\DbalOrderPaymentProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<OrderPaymentResult>
 */
final class DbalOrderPaymentFinder extends AbstractDbalFinder implements OrderPaymentFinderInterface
{
    public function ofOrderOrNull(string $orderId): ?OrderPaymentResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($orderId): void {
                $qb->andWhere('order_id = :orderId')->setParameter('orderId', $orderId);
            },
        )->one();
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('order_id', 'paid')
            ->from(DbalOrderPaymentProjector::TABLE)
            ->orderBy('order_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return OrderPaymentResult::class;
    }
}
