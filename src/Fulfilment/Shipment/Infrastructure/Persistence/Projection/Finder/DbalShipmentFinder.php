<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Fulfilment\Shipment\Infrastructure\Persistence\Projection\Projector\DbalShipmentProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<ShipmentResult>
 *
 * @phpstan-type Row array{id: string, order_id: string, customer_id: string|null, order_total_in_cents: int|null, status: string, created_at: string, dispatched_at: string|null, delivered_at: string|null, order_cancelled_at: string|null}
 */
final class DbalShipmentFinder extends AbstractDbalFinder implements ShipmentFinderInterface
{
    public function withStatus(string $status): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($status) {
                $qb->andWhere('status = :status')
                    ->setParameter('status', $status);
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'order_id', 'customer_id', 'order_total_in_cents', 'status', 'created_at', 'dispatched_at', 'delivered_at', 'order_cancelled_at')
            ->from(DbalShipmentProjector::TABLE)
            ->orderBy('created_at', 'DESC')
            ->addOrderBy('id', 'DESC');
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): ShipmentResult
    {
        return new ShipmentResult(
            id: $row['id'],
            orderId: $row['order_id'],
            customerId: $row['customer_id'],
            orderTotalInCents: null !== $row['order_total_in_cents'] ? (int) $row['order_total_in_cents'] : null,
            status: $row['status'],
            createdAt: new \DateTimeImmutable($row['created_at'], new \DateTimeZone('UTC')),
            dispatchedAt: null !== $row['dispatched_at'] ? new \DateTimeImmutable($row['dispatched_at'], new \DateTimeZone('UTC')) : null,
            deliveredAt: null !== $row['delivered_at'] ? new \DateTimeImmutable($row['delivered_at'], new \DateTimeZone('UTC')) : null,
            orderCancelledAt: null !== $row['order_cancelled_at'] ? new \DateTimeImmutable($row['order_cancelled_at'], new \DateTimeZone('UTC')) : null,
        );
    }
}
