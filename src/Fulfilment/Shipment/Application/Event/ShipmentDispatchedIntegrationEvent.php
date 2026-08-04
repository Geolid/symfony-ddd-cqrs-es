<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\Event\IntegrationEventInterface;

#[Event('fulfilment.shipment.integration.dispatched')]
final readonly class ShipmentDispatchedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $shipmentId,
        public string $orderId,
        public string $dispatchedAt,
    ) {
    }
}
