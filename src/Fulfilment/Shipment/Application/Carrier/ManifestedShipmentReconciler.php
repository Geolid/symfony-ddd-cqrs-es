<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Carrier;

use Fulfilment\Shipment\Application\Command\DispatchShipment\DispatchShipment;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class ManifestedShipmentReconciler implements ShipmentStatusReconcilerInterface
{
    public function __construct(
        private CarrierGatewayInterface $carrierGateway,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function supports(ShipmentStatus $status): bool
    {
        return ShipmentStatus::MANIFESTED === $status;
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function reconcile(string $id, string $reference): bool
    {
        if (ShipmentStatus::DISPATCHED->value !== $this->carrierGateway->checkStatus($reference)) {
            return false;
        }

        $this->commandBus->dispatch(new DispatchShipment($id));

        return true;
    }
}
