<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\IntegrationEvent\ShipmentManifested;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.fulfilment.shipment.shipment.manifested')]
final readonly class ShipmentManifestedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $shipmentId,
        public string $orderId,
        public string $trackingReference,
    ) {
    }
}
