<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Fulfilment\Shipment\Application\Enum\ShipmentStatus;
use Fulfilment\Shipment\Application\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Fulfilment\Shipment\Infrastructure\Persistence\Projection\Projector\DbalShipmentProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalCollectionFinder;

/**
 * @extends AbstractDbalCollectionFinder<ShipmentResult>
 *
 * @phpstan-type Row array{id: string, order_id: string, status: string, tracking_reference: string|null, created_at: string, dispatched_at: string|null, delivered_at: string|null, cancelled_at: string|null, order_cancelled_at: string|null}
 */
final class DbalShipmentFinder extends AbstractDbalCollectionFinder implements ShipmentFinderInterface
{
    public function ofTrackingReference(string $trackingReference): ShipmentResult
    {
        /** @var Row|false $row */
        $row = $this->query()
            ->andWhere('tracking_reference = :trackingReference')
            ->setParameter('trackingReference', $trackingReference)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            throw ShipmentResultNotFoundException::forTrackingReference($trackingReference);
        }

        return $this->mapRow($row);
    }

    public function byStatus(string ...$values): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($values) {
                $qb->andWhere($qb->expr()->in('status', ':statuses'))
                    ->setParameter('statuses', $values, ArrayParameterType::STRING);
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'order_id', 'status', 'tracking_reference', 'created_at', 'dispatched_at', 'delivered_at', 'cancelled_at', 'order_cancelled_at')
            ->from(DbalShipmentProjector::TABLE);
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): ShipmentResult
    {
        return new ShipmentResult(
            id: $row['id'],
            orderId: $row['order_id'],
            status: ShipmentStatus::from($row['status']),
            trackingReference: $row['tracking_reference'],
            createdAt: new \DateTimeImmutable($row['created_at'], new \DateTimeZone('UTC')),
            dispatchedAt: null !== $row['dispatched_at'] ? new \DateTimeImmutable($row['dispatched_at'], new \DateTimeZone('UTC')) : null,
            deliveredAt: null !== $row['delivered_at'] ? new \DateTimeImmutable($row['delivered_at'], new \DateTimeZone('UTC')) : null,
            cancelledAt: null !== $row['cancelled_at'] ? new \DateTimeImmutable($row['cancelled_at'], new \DateTimeZone('UTC')) : null,
            orderCancelledAt: null !== $row['order_cancelled_at'] ? new \DateTimeImmutable($row['order_cancelled_at'], new \DateTimeZone('UTC')) : null,
        );
    }
}
