<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Domain\Event;

use Fulfilment\Shipping\Domain\ValueObject\ShipmentDirection;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Gdpr\ErasedPostalAddressSentinel;
use Shared\Domain\ValueObject\PostalAddress;

#[Event('fulfilment.shipping.shipment.requested')]
final readonly class ShipmentRequested
{
    public function __construct(
        public string $id,
        public string $reference,
        public ShipmentDirection $direction,
        #[DataSubjectId]
        public string $buyerId,
        #[SensitiveData(fallbackCallable: new ErasedPostalAddressSentinel())]
        public PostalAddress $origin,
        #[SensitiveData(fallbackCallable: new ErasedPostalAddressSentinel())]
        public PostalAddress $destination,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
