<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.order.payment.refund_initiated')]
final readonly class OrderPaymentRefundInitiated
{
    public function __construct(
        public string $id,
        public string $orderId,
        public string $reference,
        public \DateTimeImmutable $initiatedAt,
    ) {
    }
}
