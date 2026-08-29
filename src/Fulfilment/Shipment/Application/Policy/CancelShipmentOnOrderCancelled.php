<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Policy;

use Fulfilment\Shipment\Application\Command\CancelShipment\CancelShipment;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\IntegrationEvent\OrderCancelled\OrderCancelledIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('fulfilment.shipment.cancel_shipment_on_order_cancelled')]
final readonly class CancelShipmentOnOrderCancelled
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderCancelledIntegrationEvent::class)]
    public function __invoke(OrderCancelledIntegrationEvent $event): void
    {
        $id = ShipmentId::forOrder($event->orderId);

        if (!$this->repository->has($id)) {
            return;
        }

        $this->commandBus->dispatch(new CancelShipment($id->toString()));
    }
}
