<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('fulfilment.shipment.shipment.prepared')]
final readonly class ShipmentPrepared
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $preparedAt,
    ) {
    }
}
