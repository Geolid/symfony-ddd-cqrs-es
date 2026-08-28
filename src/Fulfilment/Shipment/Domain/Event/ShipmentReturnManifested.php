<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('fulfilment.shipment.shipment.return_manifested')]
final readonly class ShipmentReturnManifested
{
    public function __construct(
        public string $id,
        public string $returnTrackingReference,
        public \DateTimeImmutable $manifestedAt,
    ) {
    }
}
