<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('fulfilment.shipment.shipment.manifested')]
final readonly class ShipmentManifested
{
    public function __construct(
        public string $id,
        public string $trackingReference,
        public \DateTimeImmutable $manifestedAt,
    ) {
    }
}
