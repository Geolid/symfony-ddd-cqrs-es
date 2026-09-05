<?php

declare(strict_types=1);

namespace Finance\Refund\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Finance\Refund\Application\Finder\RequestedPayment\Exception\RequestedPaymentResultNotFoundException;
use Finance\Refund\Application\Finder\RequestedPayment\RequestedPaymentFinderInterface;
use Finance\Refund\Application\Finder\RequestedPayment\RequestedPaymentResult;
use Finance\Refund\Infrastructure\Projection\Projector\DbalRequestedPaymentProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<RequestedPaymentResult>
 */
final class DbalRequestedPaymentFinder extends AbstractDbalFinder implements RequestedPaymentFinderInterface
{
    public function ofOrder(string $orderId): RequestedPaymentResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($orderId): void {
                $qb->andWhere('order_id = :orderId')->setParameter('orderId', $orderId);
            },
        )->one() ?? throw RequestedPaymentResultNotFoundException::forOrder($orderId);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('order_id', 'payment_id', 'amount_in_cents')
            ->from(DbalRequestedPaymentProjector::TABLE)
            ->orderBy('order_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return RequestedPaymentResult::class;
    }
}
