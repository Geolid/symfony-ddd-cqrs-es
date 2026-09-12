<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Domain\Event;

use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipping\Domain\ValueObject\TrackingNumber;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('fulfilment.shipping.shipment.manifested')]
final readonly class ShipmentManifested
{
    public function __construct(
        public ShipmentId $id,
        public TrackingNumber $trackingNumber,
        public \DateTimeImmutable $manifestedAt,
    ) {
    }
}
