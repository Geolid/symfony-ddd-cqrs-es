<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentResult;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalOrderPaymentProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<OrderPaymentResult>
 *
 * @phpstan-type Row array{id: string, order_id: string, amount_in_cents: int, reference: string, checkout_url: string, status: string, requested_at: string, captured_at: ?string}
 */
final class DbalOrderPaymentFinder extends AbstractDbalFinder implements OrderPaymentFinderInterface
{
    public function ofReference(string $reference): ?OrderPaymentResult
    {
        /** @var Row|false $row */
        $row = $this->query()
            ->andWhere('reference = :reference')
            ->setParameter('reference', $reference)
            ->executeQuery()
            ->fetchAssociative();

        return false !== $row ? $this->mapRow($row) : null;
    }

    public function ofOrder(string $orderId): ?OrderPaymentResult
    {
        /** @var Row|false $row */
        $row = $this->query()
            ->andWhere('order_id = :orderId')
            ->setParameter('orderId', $orderId)
            ->executeQuery()
            ->fetchAssociative();

        return false !== $row ? $this->mapRow($row) : null;
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'order_id', 'amount_in_cents', 'reference', 'checkout_url', 'status', 'requested_at', 'captured_at')
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
            status: $row['status'],
            requestedAt: new \DateTimeImmutable($row['requested_at'], new \DateTimeZone('UTC')),
            capturedAt: null !== $row['captured_at'] ? new \DateTimeImmutable($row['captured_at'], new \DateTimeZone('UTC')) : null,
        );
    }
}
