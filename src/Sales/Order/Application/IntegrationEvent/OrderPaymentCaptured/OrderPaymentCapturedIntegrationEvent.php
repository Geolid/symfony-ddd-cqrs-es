<?php

declare(strict_types=1);

namespace Sales\Order\Application\IntegrationEvent\OrderPaymentCaptured;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('sales.order.integration.payment_captured')]
final readonly class OrderPaymentCapturedIntegrationEvent implements IntegrationEventInterface
{
    /**
     * @param array{firstName: string, lastName: string, street: string, postalCode: string, city: string} $shippingAddress
     */
    public function __construct(
        public string $orderId,
        #[DataSubjectId]
        public string $customerId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel([
            'firstName' => 'erased',
            'lastName' => 'erased',
            'street' => 'erased',
            'postalCode' => '00000',
            'city' => 'erased',
        ]))]
        public array $shippingAddress,
        public string $capturedAt,
    ) {
    }
}
