<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Event\DomainEventInterface;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('sales.order.payment_requested')]
final readonly class OrderPaymentRequested implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $orderId,
        #[DataSubjectId]
        public string $customerId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('erased-address-%s'))]
        public string $buyerAddress,
        public int $amountInCents,
        public string $reference,
        public string $checkoutUrl,
        public string $requestedAt,
    ) {
    }
}
