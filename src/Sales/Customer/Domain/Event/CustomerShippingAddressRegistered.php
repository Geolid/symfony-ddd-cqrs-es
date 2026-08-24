<?php

declare(strict_types=1);

namespace Sales\Customer\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('sales.customer.customer.shipping_address_registered')]
final readonly class CustomerShippingAddressRegistered
{
    /**
     * @param array{firstName: string, lastName: string, street: string, postalCode: string, city: string} $address
     */
    public function __construct(
        #[DataSubjectId]
        public string $id,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel([
            'firstName' => 'erased',
            'lastName' => 'erased',
            'street' => 'erased',
            'postalCode' => '00000',
            'city' => 'erased',
        ]))]
        public array $address,
        public string $setAt,
    ) {
    }
}
