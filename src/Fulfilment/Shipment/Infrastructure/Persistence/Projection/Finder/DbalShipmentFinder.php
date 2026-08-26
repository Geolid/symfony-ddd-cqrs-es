<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Fulfilment\Shipment\Application\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Fulfilment\Shipment\Infrastructure\Persistence\Projection\Projector\DbalShipmentProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<ShipmentResult>
 */
final class DbalShipmentFinder extends AbstractDbalFinder implements ShipmentFinderInterface
{
    public function ofTrackingReference(string $trackingReference): ShipmentResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($trackingReference): void {
                $qb->andWhere('tracking_reference = :trackingReference')->setParameter('trackingReference', $trackingReference);
            },
        )->one() ?? throw ShipmentResultNotFoundException::forTrackingReference($trackingReference);
    }

    public function ofReturnTrackingReference(string $returnTrackingReference): ShipmentResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($returnTrackingReference): void {
                $qb->andWhere('return_tracking_reference = :returnTrackingReference')->setParameter('returnTrackingReference', $returnTrackingReference);
            },
        )->one() ?? throw ShipmentResultNotFoundException::forReturnTrackingReference($returnTrackingReference);
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
            ->from(DbalShipmentProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return ShipmentResult::class;
    }
}
