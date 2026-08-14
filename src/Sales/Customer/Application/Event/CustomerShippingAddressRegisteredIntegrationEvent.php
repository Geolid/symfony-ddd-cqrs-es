<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Application\Event\IntegrationEventInterface;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('sales.customer.integration.shipping_address_registered')]
final readonly class CustomerShippingAddressRegisteredIntegrationEvent implements IntegrationEventInterface
{
    /**
     * @param array{firstName: string, lastName: string, street: string, postalCode: string, city: string} $address
     */
    public function __construct(
        #[DataSubjectId]
        public string $customerId,
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
