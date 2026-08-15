<?php

declare(strict_types=1);

namespace Sales\Order\Application\Processor;

use Fulfilment\Shipment\Application\Event\ShipmentDeliveredIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Command\CompleteOrder\CompleteOrder;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Processor\Processor;

#[Processor('sales.order.complete_order_on_shipment_delivered')]
final readonly class CompleteOrderOnShipmentDelivered
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
        $this->commandBus->dispatch(new CompleteOrder($event->orderId));
    }
}
