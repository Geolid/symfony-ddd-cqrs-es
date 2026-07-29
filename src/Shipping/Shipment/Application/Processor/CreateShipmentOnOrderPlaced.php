<?php

declare(strict_types=1);

namespace Shipping\Shipment\Application\Processor;

use Ordering\Order\Application\Event\OrderPlacedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Processor;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shipping\Shipment\Application\Command\CreateShipment\CreateShipment;
use Shipping\Shipment\Domain\ShipmentId;

#[Processor('shipping.shipment.on_order_placed')]
final readonly class CreateShipmentOnOrderPlaced
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderPlacedIntegrationEvent::class)]
    public function __invoke(OrderPlacedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new CreateShipment(
            id: ShipmentId::generate()->toString(),
            orderId: $event->orderId,
            customerId: $event->customerId,
        ));
    }
}
