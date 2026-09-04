<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('fulfilment.shipping.shipment.manifested')]
final readonly class ShipmentManifested
{
    public function __construct(
        public string $id,
        public string $trackingNumber,
        public \DateTimeImmutable $manifestedAt,
    ) {
    }
}
