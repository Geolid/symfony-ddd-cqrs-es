<?php

declare(strict_types=1);

namespace Sales\Order\Application\Policy;

use Fulfilment\Shipping\Application\IntegrationEvent\ShipmentDelivered\ShipmentDeliveredIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Command\DeliverOrder\DeliverOrder;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.order.deliver_order_on_shipment_delivered')]
final readonly class DeliverOrderOnShipmentDelivered
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
    #[Subscribe(ShipmentDeliveredIntegrationEvent::class)]
    public function __invoke(ShipmentDeliveredIntegrationEvent $event): void
    {
        if (!$this->repository->has(OrderId::fromString($event->sourceId))) {
            return;
        }

        $this->commandBus->dispatch(new DeliverOrder($event->sourceId));
    }
}
