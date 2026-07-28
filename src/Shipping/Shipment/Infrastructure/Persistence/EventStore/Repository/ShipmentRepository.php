<?php

declare(strict_types=1);

namespace Shipping\Shipment\Infrastructure\Persistence\EventStore\Repository;

use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Shipping\Shipment\Domain\Exception\ShipmentNotFoundException;
use Shipping\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Shipping\Shipment\Domain\Shipment;
use Shipping\Shipment\Domain\ShipmentId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ShipmentRepository implements ShipmentRepositoryInterface
{
    /**
     * @param Repository<Shipment> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.shipping.shipment.repository')]
        private Repository $repository,
    ) {
    }

    public function has(ShipmentId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(ShipmentId $id): Shipment
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw ShipmentNotFoundException::forId($id);
        }
    }

    public function save(Shipment $shipment): void
    {
        $this->repository->save($shipment);
    }
}
