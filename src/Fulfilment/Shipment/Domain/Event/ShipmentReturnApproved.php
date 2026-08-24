<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('fulfilment.shipment.shipment.return_approved')]
final readonly class ShipmentReturnApproved
{
    public function __construct(
        public string $id,
        public string $approvedAt,
    ) {
    }
}
