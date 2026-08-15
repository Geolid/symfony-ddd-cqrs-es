<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('fulfilment.shipment.prepared')]
final readonly class ShipmentPrepared implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $preparedAt,
    ) {
    }
}
