<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Policy;

use Fulfilment\Shipment\Application\Command\CancelShipment\CancelShipment;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\IntegrationEvent\OrderCancelled\OrderCancelledIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('fulfilment.shipment.cancel_shipment_on_order_cancelled')]
final readonly class CancelShipmentOnOrderCancelled
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderCancelledIntegrationEvent::class)]
    public function __invoke(OrderCancelledIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new CancelShipment($event->orderId));
    }
}
