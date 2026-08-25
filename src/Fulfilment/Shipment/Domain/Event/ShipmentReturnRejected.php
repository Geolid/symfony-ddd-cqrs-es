<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('fulfilment.shipment.shipment.return_rejected')]
final readonly class ShipmentReturnRejected
{
    public function __construct(
        public string $id,
        public string $reason,
        public string $rejectedAt,
    ) {
    }
}
