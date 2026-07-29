<?php

declare(strict_types=1);

namespace Shipping\Shipment\Application\Command\CreateShipment;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;
use Shipping\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Shipping\Shipment\Domain\Shipment;
use Shipping\Shipment\Domain\ShipmentId;

#[AsCommandHandler]
final readonly class CreateShipmentHandler
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(CreateShipment $command): void
    {
        $shipment = Shipment::create(
            ShipmentId::fromString($command->id),
            $command->orderId,
            $command->customerId,
            $this->clock->now(),
        );

        $this->repository->save($shipment);
    }
}
