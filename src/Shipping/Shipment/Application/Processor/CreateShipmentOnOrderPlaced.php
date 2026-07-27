<?php

declare(strict_types=1);

namespace Shipping\Shipment\Application\Processor;

use Ordering\Order\Application\Event\OrderPlacedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Processor;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shipping\Shipment\Application\Command\CreateShipment\CreateShipment;
use Shipping\Shipment\Domain\ShipmentId;

/**
 * Reacts to Ordering's public Integration Event to open a Shipment — the only coupling between
 * the two Bounded Contexts is this one Integration Event; neither BC references the other's
 * Domain layer (see deptrac_bc.yaml and ADR-001). Processors carry zero business logic and must
 * stay replay-safe: dispatch a Command, call an outbound port, or enrich via a Repository —
 * never a Finder.
 */
#[Processor('shipping.shipment.on_order_placed')]
final readonly class CreateShipmentOnOrderPlaced
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    #[Subscribe(OrderPlacedIntegrationEvent::class)]
    public function __invoke(OrderPlacedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new CreateShipment(
            id: ShipmentId::generate()->toString(),
            orderId: $event->orderId,
        ));
    }
}
