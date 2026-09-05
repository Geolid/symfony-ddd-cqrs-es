<?php

declare(strict_types=1);

namespace Finance\Payer\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Gdpr\ErasedPostalAddressSentinel;
use Shared\Domain\ValueObject\PostalAddress;

#[Event('finance.payer.payer.address_registered')]
final readonly class PayerAddressRegistered
{
    public function __construct(
        #[DataSubjectId]
        public string $id,
        #[SensitiveData(fallbackCallable: new ErasedPostalAddressSentinel())]
        public PostalAddress $address,
        public \DateTimeImmutable $setAt,
    ) {
    }
}
