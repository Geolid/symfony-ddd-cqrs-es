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
    /**
     * @param array{firstName: string, lastName: string, street: string, postalCode: string, city: string} $address
     */
    public function __construct(
        #[DataSubjectId]
        public string $id,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel([
            'firstName' => 'Erased',
            'lastName' => 'Erased',
            'street' => 'Erased',
            'postalCode' => '00000',
            'city' => 'Erased',
        ]))]
        public array $address,
        public string $setAt,
    ) {
    }
}
