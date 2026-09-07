<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\IntegrationEvent\BuyerShippingAddressDefined;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;
use Shared\Domain\Pii\ErasedFieldSentinel;

#[Event('integration.sales.buyer.buyer.shipping_address_defined')]
final readonly class BuyerShippingAddressDefinedIntegrationEvent implements IntegrationEventInterface
{
    /**
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $postalAddress
     */
    public function __construct(
        #[DataSubjectId]
        public string $buyerId,
        public string $identityId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel([
            'recipientName' => 'erased',
            'address' => ['street' => 'erased', 'postalCode' => '00000', 'city' => 'erased', 'countryCode' => 'ZZ'],
        ]))]
        public array $postalAddress,
        public \DateTimeImmutable $definedAt,
    ) {
    }
}
