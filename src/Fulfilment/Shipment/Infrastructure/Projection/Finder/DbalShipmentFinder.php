<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Projection\Finder;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Fulfilment\Shipment\Application\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Shipment\Infrastructure\Projection\Projector\DbalShipmentProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<ShipmentResult>
 */
final class DbalShipmentFinder extends AbstractDbalFinder implements ShipmentFinderInterface
{
    public function ofId(string $id): ShipmentResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($id): void {
                $qb->andWhere('id = :id')->setParameter('id', $id);
            },
        )->one() ?? throw ShipmentResultNotFoundException::forId($id);
    }

    public function ofTrackingNumber(string $trackingNumber): ShipmentResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($trackingNumber): void {
                $qb->andWhere('tracking_number = :trackingNumber')->setParameter('trackingNumber', $trackingNumber);
            },
        )->one() ?? throw ShipmentResultNotFoundException::forTrackingNumber($trackingNumber);
    }

    public function ofReferenceOrNull(string $reference): ?ShipmentResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($reference): void {
                $qb->andWhere('reference = :reference')->setParameter('reference', $reference);
            },
        )->one();
    }

    public function byStatus(ShipmentStatus ...$statuses): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($statuses): void {
                $qb->andWhere('status IN (:statuses)')
                    ->setParameter('statuses', $statuses, ArrayParameterType::STRING);
            },
        );
    }

    public function stalledBefore(\DateTimeImmutable $cutoff): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($cutoff): void {
                $cutoffParam = $qb->createNamedParameter($cutoff, Types::DATETIME_IMMUTABLE);
                $manifestedParam = $qb->createNamedParameter(ShipmentStatus::MANIFESTED);
                $dispatchedParam = $qb->createNamedParameter(ShipmentStatus::DISPATCHED);

                $qb->andWhere(
                    "(status = {$manifestedParam} AND manifested_at < {$cutoffParam})
                    OR (status = {$dispatchedParam} AND dispatched_at < {$cutoffParam})",
                );
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'reference', 'status', 'tracking_number', 'created_at', 'manifested_at', 'dispatched_at', 'delivered_at', 'cancelled_at')
            ->from(DbalShipmentProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return ShipmentResult::class;
    }
}
