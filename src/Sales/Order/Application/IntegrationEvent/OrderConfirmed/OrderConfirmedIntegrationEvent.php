<?php

declare(strict_types=1);

namespace Sales\Order\Application\IntegrationEvent\OrderConfirmed;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('integration.sales.order.order.confirmed')]
final readonly class OrderConfirmedIntegrationEvent implements IntegrationEventInterface
{
    /**
     * @param array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string} $shippingAddress
     */
    public function __construct(
        public string $orderId,
        #[DataSubjectId]
        public string $customerId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel([
            'recipientName' => 'erased',
            'street' => 'erased',
            'postalCode' => '00000',
            'city' => 'erased',
            'countryCode' => 'ZZ',
        ]))]
        public array $shippingAddress,
        public \DateTimeImmutable $confirmedAt,
    ) {
    }
}
