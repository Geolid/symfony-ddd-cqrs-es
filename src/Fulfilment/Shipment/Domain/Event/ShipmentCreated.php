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
    /**
     * @param array{firstName: string, lastName: string, street: string, postalCode: string, city: string} $shippingAddress
     */
    public function __construct(
        public string $id,
        public string $orderId,
        #[DataSubjectId]
        public string $customerId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel([
            'firstName' => 'erased',
            'lastName' => 'erased',
            'street' => 'erased',
            'postalCode' => '00000',
            'city' => 'erased',
        ]))]
        public array $shippingAddress,
        public string $createdAt,
    ) {
    }
}
