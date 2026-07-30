<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\CreateShipment;

use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ShipmentId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

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
