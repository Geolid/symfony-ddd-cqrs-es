<?php

declare(strict_types=1);

namespace Finance\Refund\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\ValueObject\Money;

#[Event('finance.refund.refund.initiated')]
final readonly class RefundInitiated
{
    public function __construct(
        public string $id,
        public string $paymentId,
        public string $orderId,
        public Money $amount,
        public \DateTimeImmutable $initiatedAt,
    ) {
    }
}
