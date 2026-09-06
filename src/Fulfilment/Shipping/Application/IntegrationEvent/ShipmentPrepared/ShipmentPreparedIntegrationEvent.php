<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\IntegrationEvent\ShipmentPrepared;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.fulfilment.shipping.shipment.prepared')]
final readonly class ShipmentPreparedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $shipmentId,
        public string $sourceId,
        public \DateTimeImmutable $preparedAt,
    ) {
    }
}
