<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Order\Application\Exception\OrderPaymentResultNotFoundException;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentResult;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalOrderPaymentProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<OrderPaymentResult>
 *
 * @phpstan-type Row array{id: string, order_id: string, amount_in_cents: int, reference: string, checkout_url: string, status: string, requested_at: string, authorized_at: ?string, captured_at: ?string, failed_at: ?string, cancelled_at: ?string, refund_initiated_at: ?string, refunded_at: ?string}
 */
final class DbalOrderPaymentFinder extends AbstractDbalFinder implements OrderPaymentFinderInterface
{
    public function ofReference(string $reference): OrderPaymentResult
    {
        /** @var Row|false $row */
        $row = $this->query()
            ->andWhere('reference = :reference')
            ->setParameter('reference', $reference)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            throw OrderPaymentResultNotFoundException::forReference($reference);
        }

        return $this->mapRow($row);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'order_id', 'amount_in_cents', 'reference', 'checkout_url', 'status', 'requested_at', 'authorized_at', 'captured_at', 'failed_at', 'cancelled_at', 'refund_initiated_at', 'refunded_at')
            ->from(DbalOrderPaymentProjector::TABLE);
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): OrderPaymentResult
    {
        return new OrderPaymentResult(
            id: $row['id'],
            orderId: $row['order_id'],
            amountInCents: (int) $row['amount_in_cents'],
            reference: $row['reference'],
            checkoutUrl: $row['checkout_url'],
            status: OrderPaymentStatus::from($row['status']),
            requestedAt: new \DateTimeImmutable($row['requested_at'], new \DateTimeZone('UTC')),
            authorizedAt: null !== $row['authorized_at'] ? new \DateTimeImmutable($row['authorized_at'], new \DateTimeZone('UTC')) : null,
            capturedAt: null !== $row['captured_at'] ? new \DateTimeImmutable($row['captured_at'], new \DateTimeZone('UTC')) : null,
            failedAt: null !== $row['failed_at'] ? new \DateTimeImmutable($row['failed_at'], new \DateTimeZone('UTC')) : null,
            cancelledAt: null !== $row['cancelled_at'] ? new \DateTimeImmutable($row['cancelled_at'], new \DateTimeZone('UTC')) : null,
            refundInitiatedAt: null !== $row['refund_initiated_at'] ? new \DateTimeImmutable($row['refund_initiated_at'], new \DateTimeZone('UTC')) : null,
            refundedAt: null !== $row['refunded_at'] ? new \DateTimeImmutable($row['refunded_at'], new \DateTimeZone('UTC')) : null,
        );
    }
}
