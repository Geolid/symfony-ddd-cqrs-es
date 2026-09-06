<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\IntegrationEvent\ShipmentDelivered;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.fulfilment.shipping.shipment.delivered')]
final readonly class ShipmentDeliveredIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $shipmentId,
        public string $sourceId,
        public \DateTimeImmutable $deliveredAt,
    ) {
    }
}
