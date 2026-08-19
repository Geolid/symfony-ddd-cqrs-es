<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Fulfilment\Shipment\Application\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Shipment\Infrastructure\Persistence\Projection\Projector\DbalShipmentProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalCollectionFinder;

/**
 * @extends AbstractDbalCollectionFinder<ShipmentResult>
 *
 * @phpstan-type Row array{id: string, order_id: string, status: string, tracking_reference: string|null, return_tracking_reference: string|null, created_at: string, manifested_at: string|null, dispatched_at: string|null, delivered_at: string|null, cancelled_at: string|null, return_dispatched_at: string|null, return_received_at: string|null, return_approved_at: string|null, return_rejected_at: string|null, return_rejection_reason: string|null}
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

    public function ofReturnTrackingReference(string $returnTrackingReference): ShipmentResult
    {
        /** @var Row|false $row */
        $row = $this->query()
            ->andWhere('return_tracking_reference = :returnTrackingReference')
            ->setParameter('returnTrackingReference', $returnTrackingReference)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            throw ShipmentResultNotFoundException::forReturnTrackingReference($returnTrackingReference);
        }

        return $this->mapRow($row);
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

    public function byCustomer(string $customerId): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($customerId): void {
                $qb->andWhere('customer_id = :customerId')
                    ->setParameter('customerId', $customerId);
            },
        );
    }

    public function manifestedBefore(string $cutoff): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($cutoff): void {
                $qb->andWhere('manifested_at < :cutoff')
                    ->setParameter('cutoff', $cutoff);
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'order_id', 'status', 'tracking_reference', 'return_tracking_reference', 'created_at', 'manifested_at', 'dispatched_at', 'delivered_at', 'cancelled_at', 'return_dispatched_at', 'return_received_at', 'return_approved_at', 'return_rejected_at', 'return_rejection_reason')
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
            returnTrackingReference: $row['return_tracking_reference'],
            createdAt: new \DateTimeImmutable($row['created_at'], new \DateTimeZone('UTC')),
            manifestedAt: null !== $row['manifested_at'] ? new \DateTimeImmutable($row['manifested_at'], new \DateTimeZone('UTC')) : null,
            dispatchedAt: null !== $row['dispatched_at'] ? new \DateTimeImmutable($row['dispatched_at'], new \DateTimeZone('UTC')) : null,
            deliveredAt: null !== $row['delivered_at'] ? new \DateTimeImmutable($row['delivered_at'], new \DateTimeZone('UTC')) : null,
            cancelledAt: null !== $row['cancelled_at'] ? new \DateTimeImmutable($row['cancelled_at'], new \DateTimeZone('UTC')) : null,
            returnDispatchedAt: null !== $row['return_dispatched_at'] ? new \DateTimeImmutable($row['return_dispatched_at'], new \DateTimeZone('UTC')) : null,
            returnReceivedAt: null !== $row['return_received_at'] ? new \DateTimeImmutable($row['return_received_at'], new \DateTimeZone('UTC')) : null,
            returnApprovedAt: null !== $row['return_approved_at'] ? new \DateTimeImmutable($row['return_approved_at'], new \DateTimeZone('UTC')) : null,
            returnRejectedAt: null !== $row['return_rejected_at'] ? new \DateTimeImmutable($row['return_rejected_at'], new \DateTimeZone('UTC')) : null,
            returnRejectionReason: $row['return_rejection_reason'],
        );
    }
}
