<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('sales.order.payment_voided')]
final readonly class OrderPaymentVoided implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $orderId,
        public string $reference,
        public string $voidedAt,
    ) {
    }
}
