<?php

declare(strict_types=1);

namespace Sales\Order\Application\Policy;

use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentReturnApproved\ShipmentReturnApprovedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Command\ConfirmOrderReturn\ConfirmOrderReturn;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy\Policy;

#[Policy('sales.order.confirm_order_return_on_shipment_return_approved')]
final readonly class ConfirmOrderReturnOnShipmentReturnApproved
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(ShipmentReturnApprovedIntegrationEvent::class)]
    public function __invoke(ShipmentReturnApprovedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new ConfirmOrderReturn($event->orderId));
    }
}
