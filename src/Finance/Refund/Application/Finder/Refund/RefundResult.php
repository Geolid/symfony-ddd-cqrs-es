<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Finder\Refund;

use Finance\Refund\Application\RefundStatus;

final readonly class RefundResult
{
    public function __construct(
        public string $id,
        public string $paymentId,
        public string $orderId,
        public int $amountInCents,
        public RefundStatus $status,
        public \DateTimeImmutable $initiatedAt,
        public ?\DateTimeImmutable $refundedAt,
        public ?\DateTimeImmutable $failedAt,
    ) {
    }
}
