<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Carrier\Reconciliation;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Application\Carrier\CarrierGatewayStatus;
use Fulfilment\Shipment\Application\Command\DispatchShipmentReturn\DispatchShipmentReturn;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class ReturnManifestedShipmentReconciler implements ShipmentStatusReconcilerInterface
{
    public function __construct(
        private CarrierGatewayInterface $carrierGateway,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function supports(ShipmentStatus $status): bool
    {
        return ShipmentStatus::RETURN_MANIFESTED === $status;
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function reconcile(string $id, string $reference): bool
    {
        if (CarrierGatewayStatus::RETURN_DISPATCHED !== $this->carrierGateway->checkStatus($reference)) {
            return false;
        }

        $this->commandBus->dispatch(new DispatchShipmentReturn($id));

        return true;
    }
}
