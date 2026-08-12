<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;
use Shared\Domain\Event\DomainEventInterface;

#[Event('fulfilment.shipment.created')]
final readonly class ShipmentCreated implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $orderId,
        #[DataSubjectId]
        public string $customerId,
        #[PersonalData(fallback: 'erased-address')]
        public string $customerAddress,
        public string $createdAt,
    ) {
    }
}
