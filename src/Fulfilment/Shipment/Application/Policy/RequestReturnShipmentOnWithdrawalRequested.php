<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Policy;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalRequested\WithdrawalRequestedIntegrationEvent;
use Fulfilment\Shipment\Application\Command\RequestShipment\RequestShipment;
use Fulfilment\Shipment\Application\Warehouse\WarehouseAddressProvider;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('fulfilment.shipment.request_return_shipment_on_withdrawal_requested')]
final readonly class RequestReturnShipmentOnWithdrawalRequested
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private WarehouseAddressProvider $warehouseAddressProvider,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(WithdrawalRequestedIntegrationEvent::class)]
    public function __invoke(WithdrawalRequestedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new RequestShipment(
            id: ShipmentId::generate()->toString(),
            reference: $event->withdrawalId,
            customerId: $event->customerId,
            origin: $event->shippingAddress,
            destination: $this->warehouseAddressProvider->get()->toArray(),
        ));
    }
}
