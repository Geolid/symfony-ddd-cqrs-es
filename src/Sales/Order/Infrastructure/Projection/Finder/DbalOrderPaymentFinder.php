<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Sales\Order\Application\Exception\OrderPaymentResultNotFoundException;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentResult;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Infrastructure\Projection\Projector\DbalOrderPaymentProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<OrderPaymentResult>
 */
final class DbalOrderPaymentFinder extends AbstractDbalFinder implements OrderPaymentFinderInterface
{
    public function ofReference(string $reference): OrderPaymentResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($reference): void {
                $qb->andWhere('reference = :reference')->setParameter('reference', $reference);
            },
        )->one() ?? throw OrderPaymentResultNotFoundException::forReference($reference);
    }

    public function byStatus(OrderPaymentStatus $status): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($status): void {
                $qb->andWhere('status = :status')->setParameter('status', $status);
            },
        );
    }

    public function stalledBefore(\DateTimeImmutable $cutoff): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($cutoff): void {
                $cutoffParam = $qb->createNamedParameter($cutoff, Types::DATETIME_IMMUTABLE);
                $requestedParam = $qb->createNamedParameter(OrderPaymentStatus::REQUESTED);
                $refundInitiatedParam = $qb->createNamedParameter(OrderPaymentStatus::REFUND_INITIATED);

                $qb->andWhere(
                    "(status = {$requestedParam} AND requested_at < {$cutoffParam})
                    OR (status = {$refundInitiatedParam} AND refund_initiated_at < {$cutoffParam})",
                );
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'order_id', 'amount_in_cents', 'reference', 'checkout_url', 'status', 'requested_at', 'authorized_at', 'captured_at', 'failed_at', 'cancelled_at', 'refund_initiated_at', 'refunded_at')
            ->from(DbalOrderPaymentProjector::TABLE)
            ->orderBy('requested_at', 'ASC')
            ->addOrderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return OrderPaymentResult::class;
    }
}
