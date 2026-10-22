<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Reconciliation;

use Fulfilment\Shipping\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipping\Application\Command\DeliverShipment\DeliverShipment;
use Fulfilment\Shipping\Application\Command\DispatchShipment\DispatchShipment;
use Fulfilment\Shipping\Application\Finder\Shipment\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\Reconciliation\Exception\UnsupportedShipmentStatusException;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class ShipmentReconciler implements ShipmentReconcilerInterface
{
    public function __construct(
        private ShipmentFinderInterface $shipmentFinder,
        private CarrierGatewayInterface $carrierGateway,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ShipmentResultNotFoundException
     * @throws UnsupportedShipmentStatusException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function reconcile(string $shipmentId): void
    {
        $shipment = $this->shipmentFinder->ofId($shipmentId);

        if (!$shipment->status->isManifested() && !$shipment->status->isDispatched()) {
            throw UnsupportedShipmentStatusException::forStatus($shipment->status);
        }

        \assert(null !== $shipment->trackingNumber);

        $carrierStatus = $this->carrierGateway->checkStatus($shipment->trackingNumber);

        $command = match (true) {
            $shipment->status->isManifested() && $carrierStatus->isDispatched() => new DispatchShipment($shipmentId),
            $shipment->status->isDispatched() && $carrierStatus->isDelivered() => new DeliverShipment($shipmentId),
            default => null,
        };

        if ($command instanceof CommandInterface) {
            $this->commandBus->dispatch($command);
        }
    }
}
