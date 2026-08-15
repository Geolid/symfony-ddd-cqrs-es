<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Command\CreateShipment\CreateShipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Event\OrderConfirmedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Processor\Processor;

#[Processor('fulfilment.shipment.create_shipment_on_order_confirmed')]
final readonly class CreateShipmentOnOrderConfirmed
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderConfirmedIntegrationEvent::class)]
    public function __invoke(OrderConfirmedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new CreateShipment(
            id: ShipmentId::forOrder($event->orderId)->toString(),
            orderId: $event->orderId,
            customerId: $event->customerId,
            shippingAddress: $event->shippingAddress,
        ));
    }
}
