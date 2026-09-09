<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Policy;

use Fulfilment\Shipping\Application\IntegrationEvent\ShipmentDispatched\ShipmentDispatchedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Application\Command\DispatchOrder\DispatchOrder;
use Sales\Ordering\Domain\Repository\OrderRepositoryInterface;
use Sales\Ordering\Domain\ValueObject\OrderId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.ordering.dispatch_order_on_shipment_dispatched')]
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
        if (!$this->repository->has(OrderId::fromString($event->orderId))) {
            return;
        }

        $this->commandBus->dispatch(new DispatchOrder($event->orderId));
    }
}
