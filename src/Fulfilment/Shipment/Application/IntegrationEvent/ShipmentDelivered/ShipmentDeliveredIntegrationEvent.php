<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\IntegrationEvent\ShipmentDelivered;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.fulfilment.shipment.shipment.delivered')]
final readonly class ShipmentDeliveredIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $shipmentId,
        public string $orderId,
        public string $deliveredAt,
    ) {
    }
}
