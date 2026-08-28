<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Carrier;

use Fulfilment\Shipment\Application\Command\ReceiveShipmentReturn\ReceiveShipmentReturn;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class ReturnDispatchedShipmentReconciler implements ShipmentStatusReconcilerInterface
{
    public function __construct(
        private CarrierGatewayInterface $carrierGateway,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function supports(string $status): bool
    {
        return ShipmentStatus::RETURN_DISPATCHED->value === $status;
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function reconcile(string $id, string $reference): bool
    {
        if (ShipmentStatus::RETURN_RECEIVED->value !== $this->carrierGateway->checkStatus($reference)) {
            return false;
        }

        $this->commandBus->dispatch(new ReceiveShipmentReturn($id));

        return true;
    }
}
