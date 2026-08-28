<?php

declare(strict_types=1);

namespace Sales\Customer\Application\IntegrationEvent\CustomerShippingAddressRegistered;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('integration.sales.customer.customer.shipping_address_registered')]
final readonly class CustomerShippingAddressRegisteredIntegrationEvent implements IntegrationEventInterface
{
    /**
     * @param array{firstName: string, lastName: string, street: string, postalCode: string, city: string, countryCode: string} $address
     */
    public function __construct(
        #[DataSubjectId]
        public string $customerId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel([
            'firstName' => 'erased',
            'lastName' => 'erased',
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
