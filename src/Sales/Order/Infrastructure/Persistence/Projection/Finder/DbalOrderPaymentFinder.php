<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Order\Application\Exception\OrderPaymentResultNotFoundException;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentResult;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalOrderPaymentProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

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

    public function byStatus(string ...$statuses): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($statuses): void {
                $qb->andWhere('status IN (:statuses)')
                    ->setParameter('statuses', $statuses, ArrayParameterType::STRING);
            },
        );
    }

    public function requestedBefore(string $cutoff): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($cutoff): void {
                $qb->andWhere('requested_at < :cutoff')
                    ->setParameter('cutoff', $cutoff);
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'order_id', 'amount_in_cents', 'reference', 'checkout_url', 'status', 'requested_at', 'authorized_at', 'captured_at', 'failed_at', 'cancelled_at', 'refund_initiated_at', 'refunded_at')
            ->from(DbalOrderPaymentProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return OrderPaymentResult::class;
    }
}
