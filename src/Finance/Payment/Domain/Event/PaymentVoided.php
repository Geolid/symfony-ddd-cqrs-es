<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\Event;

use Finance\Payment\Domain\ValueObject\PaymentReference;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('finance.payment.payment.voided')]
final readonly class PaymentVoided
{
    public function __construct(
        public string $id,
        public string $orderId,
        public PaymentReference $reference,
        public \DateTimeImmutable $voidedAt,
    ) {
    }
}
