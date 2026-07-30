<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('fulfilment.shipment.created')]
final readonly class ShipmentCreated implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $orderId,
        public string $customerId,
        public string $createdAt,
    ) {
    }
}
