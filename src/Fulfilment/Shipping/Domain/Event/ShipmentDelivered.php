<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('fulfilment.shipping.shipment.delivered')]
final readonly class ShipmentDelivered
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $deliveredAt,
    ) {
    }
}
