<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Order\Application\Exception\OrderResultNotFoundException;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\Order\OrderResult;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalOrderProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalCollectionFinder;

/**
 * @extends AbstractDbalCollectionFinder<OrderResult>
 *
 * @phpstan-type Row array{id: string, customer_id: string, total_amount_in_cents: int, status: string, placed_at: string, confirmed_at: string|null, dispatched_at: string|null, delivered_at: string|null, completed_at: string|null, return_requested_at: string|null, returned_at: string|null, return_rejected_at: string|null, return_rejection_reason: string|null, cancelled_at: string|null, closed_at: string|null, anonymized_at: string|null}
 */
final class DbalOrderFinder extends AbstractDbalCollectionFinder implements OrderFinderInterface
{
    public function ofId(string $id): OrderResult
    {
        /** @var Row|false $row */
        $row = $this->query()
            ->andWhere('id = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            throw OrderResultNotFoundException::forId($id);
        }

        return $this->mapRow($row);
    }

    public function byCustomer(string $customerId): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($customerId) {
                $qb->andWhere('customer_id = :customerId')
                    ->setParameter('customerId', $customerId);
            },
        );
    }

    public function closedBefore(string $cutoff): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($cutoff) {
                $qb->andWhere('closed_at < :cutoff')
                    ->setParameter('cutoff', new \DateTimeImmutable($cutoff)->format('Y-m-d H:i:s'));
            },
        );
    }

    public function deliveredBefore(string $cutoff): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($cutoff) {
                $qb->andWhere('status = :status')
                    ->andWhere('delivered_at < :cutoff')
                    ->setParameter('status', OrderStatus::DELIVERED->value)
                    ->setParameter('cutoff', new \DateTimeImmutable($cutoff)->format('Y-m-d H:i:s'));
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'customer_id', 'total_amount_in_cents', 'status', 'placed_at', 'confirmed_at', 'dispatched_at', 'delivered_at', 'completed_at', 'return_requested_at', 'returned_at', 'return_rejected_at', 'return_rejection_reason', 'cancelled_at', 'closed_at', 'anonymized_at')
            ->from(DbalOrderProjector::TABLE);
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): OrderResult
    {
        return new OrderResult(
            id: $row['id'],
            customerId: $row['customer_id'],
            totalAmountInCents: (int) $row['total_amount_in_cents'],
            status: OrderStatus::from($row['status']),
            placedAt: new \DateTimeImmutable($row['placed_at'], new \DateTimeZone('UTC')),
            confirmedAt: null !== $row['confirmed_at'] ? new \DateTimeImmutable($row['confirmed_at'], new \DateTimeZone('UTC')) : null,
            dispatchedAt: null !== $row['dispatched_at'] ? new \DateTimeImmutable($row['dispatched_at'], new \DateTimeZone('UTC')) : null,
            deliveredAt: null !== $row['delivered_at'] ? new \DateTimeImmutable($row['delivered_at'], new \DateTimeZone('UTC')) : null,
            completedAt: null !== $row['completed_at'] ? new \DateTimeImmutable($row['completed_at'], new \DateTimeZone('UTC')) : null,
            returnRequestedAt: null !== $row['return_requested_at'] ? new \DateTimeImmutable($row['return_requested_at'], new \DateTimeZone('UTC')) : null,
            returnedAt: null !== $row['returned_at'] ? new \DateTimeImmutable($row['returned_at'], new \DateTimeZone('UTC')) : null,
            returnRejectedAt: null !== $row['return_rejected_at'] ? new \DateTimeImmutable($row['return_rejected_at'], new \DateTimeZone('UTC')) : null,
            returnRejectionReason: $row['return_rejection_reason'],
            cancelledAt: null !== $row['cancelled_at'] ? new \DateTimeImmutable($row['cancelled_at'], new \DateTimeZone('UTC')) : null,
            closedAt: null !== $row['closed_at'] ? new \DateTimeImmutable($row['closed_at'], new \DateTimeZone('UTC')) : null,
            anonymizedAt: null !== $row['anonymized_at'] ? new \DateTimeImmutable($row['anonymized_at'], new \DateTimeZone('UTC')) : null,
        );
    }
}
