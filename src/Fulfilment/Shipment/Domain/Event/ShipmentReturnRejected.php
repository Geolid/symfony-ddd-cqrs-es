<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('fulfilment.shipment.shipment.return_rejected')]
final readonly class ShipmentReturnRejected implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $reason,
        public string $rejectedAt,
    ) {
    }
}
