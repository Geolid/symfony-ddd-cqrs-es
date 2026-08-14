<?php

declare(strict_types=1);

namespace Sales\Order\Application\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Application\Event\IntegrationEventInterface;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('sales.order.integration.payment_captured')]
final readonly class OrderPaymentCapturedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        #[DataSubjectId]
        public string $customerId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $shippingFirstName,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $shippingLastName,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $shippingStreet,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('00000'))]
        public string $shippingPostalCode,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $shippingCity,
        public string $capturedAt,
    ) {
    }
}
