<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.order.payment.cancelled')]
final readonly class OrderPaymentCancelled
{
    public function __construct(
        public string $id,
        public string $orderId,
        public \DateTimeImmutable $cancelledAt,
    ) {
    }
}
