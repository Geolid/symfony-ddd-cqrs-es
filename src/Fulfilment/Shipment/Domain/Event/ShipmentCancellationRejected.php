<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('fulfilment.shipment.cancellation_rejected')]
final readonly class ShipmentCancellationRejected implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $status,
        public string $rejectedAt,
    ) {
    }
}
