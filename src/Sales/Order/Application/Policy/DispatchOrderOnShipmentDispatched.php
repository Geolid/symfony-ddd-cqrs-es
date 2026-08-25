<?php

declare(strict_types=1);

namespace Sales\Order\Application\Policy;

use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentDispatched\ShipmentDispatchedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Command\DispatchOrder\DispatchOrder;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy\Policy;

#[Policy('sales.order.dispatch_order_on_shipment_dispatched')]
final readonly class DispatchOrderOnShipmentDispatched
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(ShipmentDispatchedIntegrationEvent::class)]
    public function __invoke(ShipmentDispatchedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new DispatchOrder($event->orderId));
    }
}
