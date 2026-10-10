<?php

declare(strict_types=1);

namespace Crm\Customer\Application\IntegrationEvent\CustomerBillingAddressDefined;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;
use Shared\Domain\Pii\ErasedFieldSentinel;

#[Event('integration.crm.customer.customer.billing_address_defined')]
final readonly class CustomerBillingAddressDefinedIntegrationEvent implements IntegrationEventInterface
{
    /**
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $postalAddress
     */
    public function __construct(
        #[DataSubjectId]
        public string $customerId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel([
            'recipientName' => 'erased',
            'address' => ['street' => 'erased', 'postalCode' => '00000', 'city' => 'erased', 'countryCode' => 'ZZ'],
        ]))]
        public array $postalAddress,
        public \DateTimeImmutable $definedAt,
    ) {
    }
}
