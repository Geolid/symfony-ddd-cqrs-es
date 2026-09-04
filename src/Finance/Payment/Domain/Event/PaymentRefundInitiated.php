<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('finance.payment.payment.refund_initiated')]
final readonly class PaymentRefundInitiated
{
    public function __construct(
        public string $id,
        public string $orderId,
        public string $reference,
        public \DateTimeImmutable $initiatedAt,
    ) {
    }
}
