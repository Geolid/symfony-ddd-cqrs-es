<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.order.order_payment.refunded')]
final readonly class OrderPaymentRefunded
{
    public function __construct(
        public string $id,
        public string $orderId,
        public \DateTimeImmutable $refundedAt,
    ) {
    }
}
