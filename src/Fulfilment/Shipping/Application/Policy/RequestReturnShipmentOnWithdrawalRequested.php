<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Policy;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalRequested\WithdrawalRequestedIntegrationEvent;
use Fulfilment\Shipping\Application\Command\RequestShipment\RequestShipment;
use Fulfilment\Shipping\Application\ShipmentDirection;
use Fulfilment\Shipping\Application\Warehouse\WarehouseAddressProvider;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('fulfilment.shipping.request_return_shipment_on_withdrawal_requested')]
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
            sourceId: $event->withdrawalId,
            direction: ShipmentDirection::RETURN,
            buyerId: $event->buyerId,
            origin: $event->shippingAddress,
            destination: $this->warehouseAddressProvider->get()->toArray(),
        ));
    }
}
