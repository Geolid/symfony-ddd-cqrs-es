<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Command\CreateShipment\CreateShipment;
use Fulfilment\Shipment\Domain\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Processor;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Event\OrderPlacedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

#[Processor('fulfilment.shipment.create_shipment_on_order_placed')]
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
