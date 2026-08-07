<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\OrderPayment;

use Sales\Order\Application\Enum\AppOrderPaymentStatus;
use Shared\Application\Result\ResultInterface;

final readonly class OrderPaymentResult implements ResultInterface
{
    public function __construct(
        public string $id,
        public string $orderId,
        public int $amountInCents,
        public string $reference,
        public string $checkoutUrl,
        public AppOrderPaymentStatus $status,
        public \DateTimeImmutable $requestedAt,
        public ?\DateTimeImmutable $capturedAt,
    ) {
    }
}
