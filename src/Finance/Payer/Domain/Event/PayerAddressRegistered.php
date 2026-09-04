<?php

declare(strict_types=1);

namespace Finance\Payer\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('finance.payer.payer.address_registered')]
final readonly class PayerAddressRegistered
{
    /**
     * @param array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string} $address
     */
    public function __construct(
        #[DataSubjectId]
        public string $id,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel([
            'recipientName' => 'erased',
            'street' => 'erased',
            'postalCode' => '00000',
            'city' => 'erased',
            'countryCode' => 'ZZ',
        ]))]
        public array $address,
        public \DateTimeImmutable $setAt,
    ) {
    }
}
