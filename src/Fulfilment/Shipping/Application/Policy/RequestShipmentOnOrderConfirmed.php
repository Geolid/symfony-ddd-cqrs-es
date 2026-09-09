<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Policy;

use Fulfilment\Shipping\Application\Command\RequestShipment\RequestShipment;
use Fulfilment\Shipping\Application\Warehouse\WarehouseAddressProvider;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Application\IntegrationEvent\OrderConfirmed\OrderConfirmedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Application\Policy;

#[Policy('fulfilment.shipping.request_shipment_on_order_confirmed')]
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
            id: ShipmentId::forOrder($event->orderId)->toString(),
            orderId: $event->orderId,
            buyerId: $event->buyerId,
            origin: PostalAddressMapper::toArray($this->warehouseAddressProvider->get()),
            destination: $event->shippingAddress,
        ));
    }
}
