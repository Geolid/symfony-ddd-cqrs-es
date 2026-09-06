<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\IntegrationEvent\ShipmentDispatched;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.fulfilment.shipping.shipment.dispatched')]
final readonly class ShipmentDispatchedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $shipmentId,
        public string $sourceId,
        public \DateTimeImmutable $dispatchedAt,
    ) {
    }
}
