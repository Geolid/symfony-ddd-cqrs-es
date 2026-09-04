<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Infrastructure\EventStore;

use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyExistsException;
use Fulfilment\Shipping\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipping\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipping\Domain\Shipment;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelShipmentRepository implements ShipmentRepositoryInterface
{
    /**
     * @param Repository<Shipment> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.fulfilment.shipping.shipment.repository')]
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
            throw ShipmentNotFoundException::forId($id->toString());
        }
    }

    public function save(Shipment $shipment): void
    {
        try {
            $this->repository->save($shipment);
        } catch (AggregateAlreadyExists) {
            throw ShipmentAlreadyExistsException::forId($shipment->id->toString());
        }
    }
}
