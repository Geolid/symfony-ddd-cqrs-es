<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Domain\Event;

use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Pii\ErasedPostalAddress;
use Shared\Domain\ValueObject\PostalAddress;

#[Event('fulfilment.shipping.shipment.requested')]
final readonly class ShipmentRequested
{
    public function __construct(
        #[DataSubjectId]
        public ShipmentId $id,
        public string $orderId,
        public string $customerId,
        #[SensitiveData(fallbackCallable: new ErasedPostalAddress())]
        public PostalAddress $origin,
        #[SensitiveData(fallbackCallable: new ErasedPostalAddress())]
        public PostalAddress $destination,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
