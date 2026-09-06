<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\IntegrationEvent\BuyerPostalAddressDefined;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('integration.sales.buyer.buyer.postal_address_defined')]
final readonly class BuyerPostalAddressDefinedIntegrationEvent implements IntegrationEventInterface
{
    /**
     * @param array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string} $postalAddress
     */
    public function __construct(
        #[DataSubjectId]
        public string $buyerId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel([
            'recipientName' => 'erased',
            'street' => 'erased',
            'postalCode' => '00000',
            'city' => 'erased',
            'countryCode' => 'ZZ',
        ]))]
        public array $postalAddress,
        public \DateTimeImmutable $definedAt,
    ) {
    }
}
