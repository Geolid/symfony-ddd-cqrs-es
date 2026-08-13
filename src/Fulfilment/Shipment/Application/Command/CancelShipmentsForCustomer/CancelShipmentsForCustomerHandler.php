<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\CancelShipmentsForCustomer;

use Fulfilment\Shipment\Application\Command\CancelShipment\CancelShipment;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentState;
use Shared\Application\Command\AsCommandHandler;
use Shared\Application\Command\CommandBusInterface;

#[AsCommandHandler]
final readonly class CancelShipmentsForCustomerHandler
{
    public function __construct(
        private ShipmentFinderInterface $finder,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(CancelShipmentsForCustomer $command): void
    {
        $cancellableStatuses = array_map(static fn (ShipmentState $status) => $status->value, ShipmentState::cancellableStatuses());

        foreach ($this->finder->byCustomer($command->customerId)->byStatus(...$cancellableStatuses) as $shipment) {
            $this->commandBus->dispatch(new CancelShipment($shipment->id));
        }
    }
}
