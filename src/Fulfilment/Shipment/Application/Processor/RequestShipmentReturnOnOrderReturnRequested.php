<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Command\RequestShipmentReturn\RequestShipmentReturn;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Event\OrderReturnRequestedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Processor\Processor;

#[Processor('fulfilment.shipment.request_shipment_return_on_order_return_requested')]
final readonly class RequestShipmentReturnOnOrderReturnRequested
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderReturnRequestedIntegrationEvent::class)]
    public function __invoke(OrderReturnRequestedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new RequestShipmentReturn(
            ShipmentId::forOrder($event->orderId)->toString(),
        ));
    }
}
