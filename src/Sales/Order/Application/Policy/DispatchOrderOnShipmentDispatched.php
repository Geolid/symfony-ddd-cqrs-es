<?php

declare(strict_types=1);

namespace Sales\Order\Application\Policy;

use Fulfilment\Shipping\Application\IntegrationEvent\ShipmentDispatched\ShipmentDispatchedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Command\DispatchOrder\DispatchOrder;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

/**
 * A `Shipment` reports DISPATCHED for both an outbound leg and a return
 * pickup — its `reference` is only meaningful to whichever BC requested it,
 * so a relevance guard against this BC's own repository decides whether it
 * applies.
 */
#[Policy('sales.order.dispatch_order_on_shipment_dispatched')]
final readonly class DispatchOrderOnShipmentDispatched
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(ShipmentDispatchedIntegrationEvent::class)]
    public function __invoke(ShipmentDispatchedIntegrationEvent $event): void
    {
        if (!$this->repository->has(OrderId::fromString($event->reference))) {
            return;
        }

        $this->commandBus->dispatch(new DispatchOrder($event->reference));
    }
}
