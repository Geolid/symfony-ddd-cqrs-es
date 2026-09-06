<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Finder\Payment;

use Finance\Payment\Application\PaymentStatus;

final readonly class PaymentResult
{
    public function __construct(
        public string $id,
        public string $orderId,
        public int $amountInCents,
        public string $reference,
        public string $checkoutUrl,
        public PaymentStatus $status,
        public \DateTimeImmutable $requestedAt,
        public ?\DateTimeImmutable $authorizedAt,
        public ?\DateTimeImmutable $capturedAt,
        public ?\DateTimeImmutable $failedAt,
        public ?\DateTimeImmutable $cancelledAt,
        public ?\DateTimeImmutable $refundRequestedAt,
        public ?\DateTimeImmutable $refundFailedAt,
        public ?\DateTimeImmutable $refundedAt,
    ) {
    }
}
