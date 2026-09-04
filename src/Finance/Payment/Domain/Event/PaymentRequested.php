<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('finance.payment.payment.requested')]
final readonly class PaymentRequested
{
    public function __construct(
        public string $id,
        public string $orderId,
        public int $amountInCents,
        public string $reference,
        public string $checkoutUrl,
        public \DateTimeImmutable $requestedAt,
    ) {
    }
}
