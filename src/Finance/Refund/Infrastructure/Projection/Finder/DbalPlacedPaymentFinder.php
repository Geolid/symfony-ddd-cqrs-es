<?php

declare(strict_types=1);

namespace Finance\Refund\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Finance\Refund\Application\Exception\PlacedPaymentResultNotFoundException;
use Finance\Refund\Application\Finder\PlacedPayment\PlacedPaymentFinderInterface;
use Finance\Refund\Application\Finder\PlacedPayment\PlacedPaymentResult;
use Finance\Refund\Infrastructure\Projection\Projector\DbalPlacedPaymentProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<PlacedPaymentResult>
 */
final class DbalPlacedPaymentFinder extends AbstractDbalFinder implements PlacedPaymentFinderInterface
{
    public function ofOrder(string $orderId): PlacedPaymentResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($orderId): void {
                $qb->andWhere('order_id = :orderId')->setParameter('orderId', $orderId);
            },
        )->one() ?? throw PlacedPaymentResultNotFoundException::forOrder($orderId);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('order_id', 'payment_id', 'amount_in_cents')
            ->from(DbalPlacedPaymentProjector::TABLE)
            ->orderBy('order_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return PlacedPaymentResult::class;
    }
}
