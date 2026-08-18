<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('fulfilment.shipment.return_manifested')]
final readonly class ShipmentReturnManifested implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $returnTrackingReference,
        public string $manifestedAt,
    ) {
    }
}
