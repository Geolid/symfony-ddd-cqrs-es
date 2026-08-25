<?php

declare(strict_types=1);

namespace Sales\Order\Application\Policy;

use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentReturnRejected\ShipmentReturnRejectedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Command\RejectOrderReturn\RejectOrderReturn;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy\Policy;

#[Policy('sales.order.reject_order_return_on_shipment_return_rejected')]
final readonly class RejectOrderReturnOnShipmentReturnRejected
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(ShipmentReturnRejectedIntegrationEvent::class)]
    public function __invoke(ShipmentReturnRejectedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new RejectOrderReturn($event->orderId, $event->reason));
    }
}
