<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('finance.payment.payment.abandoned')]
final readonly class PaymentAbandoned
{
    public function __construct(
        public string $id,
        public string $checkoutSessionId,
        public \DateTimeImmutable $abandonedAt,
    ) {
    }
}
