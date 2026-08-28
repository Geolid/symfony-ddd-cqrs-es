<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Event;

use Fulfilment\Shipment\Domain\ValueObject\ShipmentState;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('fulfilment.shipment.shipment.cancellation_rejected')]
final readonly class ShipmentCancellationRejected
{
    public function __construct(
        public string $id,
        public ShipmentState $state,
        public \DateTimeImmutable $rejectedAt,
    ) {
    }
}
