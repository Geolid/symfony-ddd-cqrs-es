<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('sales.order.payment.requested')]
final readonly class OrderPaymentRequested implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $orderId,
        public int $amountInCents,
        public string $reference,
        public string $checkoutUrl,
        public string $requestedAt,
    ) {
    }
}
