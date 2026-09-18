<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Reconciliation;

use Fulfilment\Shipping\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipping\Application\Command\DispatchShipment\DispatchShipment;
use Fulfilment\Shipping\Application\ShipmentStatus;
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
        return $status->isManifested();
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function reconcile(string $id, string $reference): bool
    {
        if (!$this->carrierGateway->checkStatus($reference)->isDispatched()) {
            return false;
        }

        $this->commandBus->dispatch(new DispatchShipment($id));

        return true;
    }
}
