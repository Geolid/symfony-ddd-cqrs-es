<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('fulfilment.shipment.shipment.cancelled')]
final readonly class ShipmentCancelled
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $cancelledAt,
    ) {
    }
}
