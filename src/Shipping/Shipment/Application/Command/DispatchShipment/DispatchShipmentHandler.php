<?php

declare(strict_types=1);

namespace Shipping\Shipment\Application\Command\DispatchShipment;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;
use Shipping\Shipment\Domain\Exception\InvalidShipmentTransitionException;
use Shipping\Shipment\Domain\Exception\ShipmentNotFoundException;
use Shipping\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Shipping\Shipment\Domain\ShipmentId;

#[AsCommandHandler]
final readonly class DispatchShipmentHandler
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ShipmentNotFoundException
     * @throws InvalidShipmentTransitionException
     */
    public function __invoke(DispatchShipment $command): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($command->id));
        $shipment->dispatch($this->clock->now());
        $this->repository->save($shipment);
    }
}
