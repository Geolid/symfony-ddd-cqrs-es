<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Policy;

use Fulfilment\Shipping\Application\Command\CancelShipment\CancelShipment;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\IntegrationEvent\OrderAborted\OrderAbortedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('fulfilment.shipping.cancel_shipment_on_order_aborted')]
final readonly class CancelShipmentOnOrderAborted
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderAbortedIntegrationEvent::class)]
    public function __invoke(OrderAbortedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new CancelShipment($event->orderId));
    }
}
