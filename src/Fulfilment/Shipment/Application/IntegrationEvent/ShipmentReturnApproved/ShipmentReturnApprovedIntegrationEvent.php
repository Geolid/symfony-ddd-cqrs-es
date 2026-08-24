<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\IntegrationEvent\ShipmentReturnApproved;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.fulfilment.shipment.shipment.return_approved')]
final readonly class ShipmentReturnApprovedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $shipmentId,
        public string $orderId,
        public string $approvedAt,
    ) {
    }
}
