<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\IntegrationEvent\OrderConfirmed;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;
use Shared\Domain\Pii\ErasedFieldSentinel;

#[Event('integration.sales.ordering.order.confirmed')]
final readonly class OrderConfirmedIntegrationEvent implements IntegrationEventInterface
{
    /**
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $shippingAddress
     */
    public function __construct(
        #[DataSubjectId]
        public string $orderId,
        public string $cartId,
        public string $shopperId,
        public string $checkoutSessionId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel([
            'recipientName' => 'erased',
            'address' => ['street' => 'erased', 'postalCode' => '00000', 'city' => 'erased', 'countryCode' => 'ZZ'],
        ]))]
        public array $shippingAddress,
        public \DateTimeImmutable $confirmedAt,
    ) {
    }
}
