<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('finance.payment.payment.refund_rejected')]
final readonly class PaymentRefundRejected
{
    public function __construct(
        public string $id,
        public string $orderId,
        public \DateTimeImmutable $rejectedAt,
    ) {
    }
}
