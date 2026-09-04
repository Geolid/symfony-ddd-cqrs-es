<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Domain\Event;

use Fulfilment\Shipping\Domain\ValueObject\ShipmentState;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('fulfilment.shipping.shipment.cancellation_rejected')]
final readonly class ShipmentCancellationRejected
{
    public function __construct(
        public string $id,
        public ShipmentState $state,
        public \DateTimeImmutable $rejectedAt,
    ) {
    }
}
