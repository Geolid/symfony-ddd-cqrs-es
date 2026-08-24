<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.order.payment.failed')]
final readonly class OrderPaymentFailed
{
    public function __construct(
        public string $id,
        public string $orderId,
        public string $failedAt,
    ) {
    }
}
