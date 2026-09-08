<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('fulfilment.shipping.shipment.erased')]
final readonly class ShipmentErased
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $erasedAt,
    ) {
    }
}
