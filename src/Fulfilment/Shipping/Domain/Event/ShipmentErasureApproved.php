<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Domain\Event;

use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('fulfilment.shipping.shipment.erasure_approved')]
final readonly class ShipmentErasureApproved
{
    public function __construct(
        public ShipmentId $id,
        public \DateTimeImmutable $approvedAt,
    ) {
    }
}
