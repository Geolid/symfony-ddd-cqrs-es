<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Policy;

use Fulfilment\Shipment\Application\Command\RequestShipment\RequestShipment;
use Fulfilment\Shipment\Application\Warehouse\WarehouseAddressProvider;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\IntegrationEvent\OrderConfirmed\OrderConfirmedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('fulfilment.shipment.request_shipment_on_order_confirmed')]
final readonly class RequestShipmentOnOrderConfirmed
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
    #[Subscribe(OrderConfirmedIntegrationEvent::class)]
    public function __invoke(OrderConfirmedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new RequestShipment(
            id: ShipmentId::generate()->toString(),
            reference: $event->orderId,
            customerId: $event->customerId,
            origin: $this->warehouseAddressProvider->get()->toArray(),
            destination: $event->shippingAddress,
        ));
    }
}
