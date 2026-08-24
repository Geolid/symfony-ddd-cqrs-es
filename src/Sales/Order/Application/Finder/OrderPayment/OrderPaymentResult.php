<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\OrderPayment;

use Sales\Order\Application\Status\OrderPaymentStatus;

final readonly class OrderPaymentResult
{
    public function __construct(
        public string $id,
        public string $orderId,
        public int $amountInCents,
        public string $reference,
        public string $checkoutUrl,
        public OrderPaymentStatus $status,
        public \DateTimeImmutable $requestedAt,
        public ?\DateTimeImmutable $authorizedAt,
        public ?\DateTimeImmutable $capturedAt,
        public ?\DateTimeImmutable $failedAt,
        public ?\DateTimeImmutable $cancelledAt,
        public ?\DateTimeImmutable $refundInitiatedAt,
        public ?\DateTimeImmutable $refundedAt,
    ) {
    }
}
