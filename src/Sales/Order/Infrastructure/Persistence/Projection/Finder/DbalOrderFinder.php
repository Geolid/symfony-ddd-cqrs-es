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
 * @phpstan-type Row array{id: string, customer_id: string, total_amount_in_cents: int, status: string, placed_at: string, confirmed_at: string|null, dispatched_at: string|null, completed_at: string|null, cancelled_at: string|null, anonymized_at: string|null}
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

    public function placedBefore(string $cutoff): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($cutoff) {
                $qb->andWhere('placed_at < :cutoff')
                    ->setParameter('cutoff', new \DateTimeImmutable($cutoff)->format('Y-m-d H:i:s'));
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'customer_id', 'total_amount_in_cents', 'status', 'placed_at', 'confirmed_at', 'dispatched_at', 'completed_at', 'cancelled_at', 'anonymized_at')
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
            completedAt: null !== $row['completed_at'] ? new \DateTimeImmutable($row['completed_at'], new \DateTimeZone('UTC')) : null,
            cancelledAt: null !== $row['cancelled_at'] ? new \DateTimeImmutable($row['cancelled_at'], new \DateTimeZone('UTC')) : null,
            anonymizedAt: null !== $row['anonymized_at'] ? new \DateTimeImmutable($row['anonymized_at'], new \DateTimeZone('UTC')) : null,
        );
    }
}
