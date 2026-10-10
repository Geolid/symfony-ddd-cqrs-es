<?php

declare(strict_types=1);

namespace Crm\Customer\Application\IntegrationEvent\CustomerBillingAddressDefined;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\Pii\ErasedPostalAddress;

#[Event('integration.crm.customer.customer.billing_address_defined')]
final readonly class CustomerBillingAddressDefinedIntegrationEvent implements IntegrationEventInterface
{
    /**
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $postalAddress
     */
    public function __construct(
        #[DataSubjectId]
        public string $customerId,
        #[SensitiveData(fallbackCallable: static function (string $subjectId): array {
            return PostalAddressMapper::toArray((new ErasedPostalAddress())());
        })]
        public array $postalAddress,
        public \DateTimeImmutable $definedAt,
    ) {
    }
}
