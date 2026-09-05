<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Domain\Event;

use Fulfilment\Shipping\Domain\ValueObject\ShipmentDirection;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('fulfilment.shipping.shipment.requested')]
final readonly class ShipmentRequested
{
    private const array ERASED_ADDRESS = [
        'recipientName' => 'erased',
        'street' => 'erased',
        'postalCode' => '00000',
        'city' => 'erased',
        'countryCode' => 'ZZ',
    ];

    /**
     * @param array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string} $origin
     * @param array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string} $destination
     */
    public function __construct(
        public string $id,
        public string $reference,
        public ShipmentDirection $direction,
        #[DataSubjectId]
        public string $buyerId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel(self::ERASED_ADDRESS))]
        public array $origin,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel(self::ERASED_ADDRESS))]
        public array $destination,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
