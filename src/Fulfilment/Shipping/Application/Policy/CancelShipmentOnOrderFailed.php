<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Policy;

use Fulfilment\Shipping\Application\Command\CancelShipment\CancelShipment;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Application\IntegrationEvent\OrderFailed\OrderFailedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('fulfilment.shipping.cancel_shipment_on_order_failed')]
final readonly class CancelShipmentOnOrderFailed
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderFailedIntegrationEvent::class)]
    public function __invoke(OrderFailedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new CancelShipment($event->orderId));
    }
}
