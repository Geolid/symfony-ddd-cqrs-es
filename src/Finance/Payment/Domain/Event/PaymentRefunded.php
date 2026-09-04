<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('finance.payment.payment.refunded')]
final readonly class PaymentRefunded
{
    public function __construct(
        public string $id,
        public string $orderId,
        public \DateTimeImmutable $refundedAt,
    ) {
    }
}
