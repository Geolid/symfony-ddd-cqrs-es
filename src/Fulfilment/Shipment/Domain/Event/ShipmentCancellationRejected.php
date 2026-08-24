<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('fulfilment.shipment.shipment.cancellation_rejected')]
final readonly class ShipmentCancellationRejected
{
    public function __construct(
        public string $id,
        public string $status,
        public string $rejectedAt,
    ) {
    }
}
