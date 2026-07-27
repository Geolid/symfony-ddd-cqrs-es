<?php

declare(strict_types=1);

namespace Shipping\Shipment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('shipping.shipment.delivered')]
final readonly class ShipmentDelivered implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $deliveredAt,
    ) {
    }
}
