<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\Event;

use Finance\Payment\Domain\ValueObject\PaymentReference;
use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\ValueObject\Money;

#[Event('finance.payment.payment.requested')]
final readonly class PaymentRequested
{
    public function __construct(
        public string $id,
        public string $orderId,
        public Money $amount,
        public PaymentReference $reference,
        public string $checkoutUrl,
        public \DateTimeImmutable $requestedAt,
    ) {
    }
}
