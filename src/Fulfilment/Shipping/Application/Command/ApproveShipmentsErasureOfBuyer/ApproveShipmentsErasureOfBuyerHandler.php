<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Command\ApproveShipmentsErasureOfBuyer;

use Fulfilment\Shipping\Application\Command\ApproveShipmentErasure\ApproveShipmentErasure;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Exception\ApplicationExceptionInterface;

#[CommandHandler]
final readonly class ApproveShipmentsErasureOfBuyerHandler
{
    public function __construct(
        private ShipmentFinderInterface $shipmentFinder,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function __invoke(ApproveShipmentsErasureOfBuyer $command): void
    {
        foreach ($this->shipmentFinder->byBuyer($command->buyerId) as $shipment) {
            $this->commandBus->dispatch(new ApproveShipmentErasure($shipment->id));
        }
    }
}
