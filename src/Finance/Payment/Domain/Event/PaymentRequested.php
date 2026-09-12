<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\Event;

use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Payment\Domain\ValueObject\PaymentReference;
use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\ValueObject\Money;

#[Event('finance.payment.payment.requested')]
final readonly class PaymentRequested
{
    public function __construct(
        public PaymentId $id,
        public string $checkoutSessionId,
        public Money $amount,
        public PaymentReference $reference,
        public string $checkoutUrl,
        public \DateTimeImmutable $requestedAt,
    ) {
    }
}
