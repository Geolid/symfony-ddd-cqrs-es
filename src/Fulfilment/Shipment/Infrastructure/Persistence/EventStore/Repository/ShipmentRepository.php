<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Persistence\EventStore\Repository;

use Fulfilment\Shipment\Domain\Exception\ShipmentAlreadyExistsException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ShipmentRepository implements ShipmentRepositoryInterface
{
    /**
     * @param Repository<Shipment> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.fulfilment.shipment.shipment.repository')]
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
            throw ShipmentAlreadyExistsException::forId($shipment->id()->toString());
        }
    }
}
