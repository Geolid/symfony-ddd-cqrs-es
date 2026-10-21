<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Policy;

use Fulfilment\Shipping\Application\IntegrationEvent\ShipmentDelivered\ShipmentDeliveredIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Application\Command\DeliverOrder\DeliverOrder;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.ordering.deliver_order_on_shipment_delivered')]
final readonly class DeliverOrderOnShipmentDelivered
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(ShipmentDeliveredIntegrationEvent::class)]
    public function __invoke(ShipmentDeliveredIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new DeliverOrder($event->orderId));
    }
}
