<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\Event;

use Finance\Payment\Domain\ValueObject\PaymentId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('finance.payment.payment.abandoned')]
final readonly class PaymentAbandoned
{
    public function __construct(
        public PaymentId $id,
        public string $checkoutSessionId,
        public \DateTimeImmutable $abandonedAt,
    ) {
    }
}
