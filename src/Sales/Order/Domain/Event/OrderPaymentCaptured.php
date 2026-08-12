<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;
use Shared\Domain\Event\DomainEventInterface;

#[Event('sales.order.payment_captured')]
final readonly class OrderPaymentCaptured implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $orderId,
        #[DataSubjectId]
        public string $customerId,
        #[PersonalData(fallback: 'erased-address')]
        public string $buyerAddress,
        public string $capturedAt,
    ) {
    }
}
