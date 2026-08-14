<?php

declare(strict_types=1);

namespace Sales\Customer\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Event\DomainEventInterface;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('sales.customer.billing_address_set')]
final readonly class CustomerBillingAddressSet implements DomainEventInterface
{
    public function __construct(
        #[DataSubjectId]
        public string $id,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $firstName,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $lastName,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $street,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('00000'))]
        public string $postalCode,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $city,
        public string $setAt,
    ) {
    }
}
