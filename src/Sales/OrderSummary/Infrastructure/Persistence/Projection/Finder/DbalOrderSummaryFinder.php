<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\OrderSummary\Application\Enum\OrderSummaryStatus;
use Sales\OrderSummary\Application\Exception\OrderSummaryResultNotFoundException;
use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryFinderInterface;
use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryResult;
use Sales\OrderSummary\Infrastructure\Persistence\Projection\Projector\DbalOrderSummaryProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalCollectionFinder;

/**
 * @extends AbstractDbalCollectionFinder<OrderSummaryResult>
 *
 * @phpstan-type Row array{order_id: string, customer_id: string, total_amount_in_cents: int|string, status: string, placed_at: string, cancelled_at: ?string, payment_amount_in_cents: int|string|null, payment_reference: ?string, payment_checkout_url: ?string, paid_at: ?string, tracking_reference: ?string, dispatched_at: ?string, delivered_at: ?string}
 */
final class DbalOrderSummaryFinder extends AbstractDbalCollectionFinder implements OrderSummaryFinderInterface
{
    public function ofOrder(string $orderId): OrderSummaryResult
    {
        /** @var Row|false $row */
        $row = $this->query()
            ->andWhere('order_id = :orderId')
            ->setParameter('orderId', $orderId)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            throw OrderSummaryResultNotFoundException::forOrderId($orderId);
        }

        return $this->mapRow($row);
    }

    public function withCustomer(string $customerId): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($customerId) {
                $qb->andWhere('customer_id = :customerId')
                    ->setParameter('customerId', $customerId);
            },
        );
    }

    public function withStatus(string $status): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($status) {
                $qb->andWhere('status = :status')
                    ->setParameter('status', $status);
            },
        );
    }

    public function sortedByPlacedAt(): static
    {
        return $this->filter(static function (QueryBuilder $qb): void {
            $qb->orderBy('placed_at', 'DESC')->addOrderBy('order_id', 'DESC');
        });
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select(
            'order_id',
            'customer_id',
            'total_amount_in_cents',
            'status',
            'placed_at',
            'cancelled_at',
            'payment_amount_in_cents',
            'payment_reference',
            'payment_checkout_url',
            'paid_at',
            'tracking_reference',
            'dispatched_at',
            'delivered_at',
        )
            ->from(DbalOrderSummaryProjector::TABLE);
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): OrderSummaryResult
    {
        return new OrderSummaryResult(
            orderId: $row['order_id'],
            customerId: $row['customer_id'],
            totalAmountInCents: (int) $row['total_amount_in_cents'],
            status: OrderSummaryStatus::from($row['status']),
            placedAt: new \DateTimeImmutable($row['placed_at'], new \DateTimeZone('UTC')),
            cancelledAt: null !== $row['cancelled_at'] ? new \DateTimeImmutable($row['cancelled_at'], new \DateTimeZone('UTC')) : null,
            paymentAmountInCents: null !== $row['payment_amount_in_cents'] ? (int) $row['payment_amount_in_cents'] : null,
            paymentReference: $row['payment_reference'],
            paymentCheckoutUrl: $row['payment_checkout_url'],
            paidAt: null !== $row['paid_at'] ? new \DateTimeImmutable($row['paid_at'], new \DateTimeZone('UTC')) : null,
            trackingReference: $row['tracking_reference'],
            dispatchedAt: null !== $row['dispatched_at'] ? new \DateTimeImmutable($row['dispatched_at'], new \DateTimeZone('UTC')) : null,
            deliveredAt: null !== $row['delivered_at'] ? new \DateTimeImmutable($row['delivered_at'], new \DateTimeZone('UTC')) : null,
        );
    }
}
