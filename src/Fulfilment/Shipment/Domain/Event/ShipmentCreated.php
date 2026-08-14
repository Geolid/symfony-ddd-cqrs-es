<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Event\DomainEventInterface;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('fulfilment.shipment.created')]
final readonly class ShipmentCreated implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $orderId,
        #[DataSubjectId]
        public string $customerId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $shippingFirstName,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $shippingLastName,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $shippingStreet,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('00000'))]
        public string $shippingPostalCode,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $shippingCity,
        public string $createdAt,
    ) {
    }
}
