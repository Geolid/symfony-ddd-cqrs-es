<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Finder\OrderSummary;

use Sales\OrderSummary\Application\Status\OrderSummaryStatus;
use Shared\Application\Result\ResultInterface;

final readonly class OrderSummaryResult implements ResultInterface
{
    public function __construct(
        public string $orderId,
        public string $customerId,
        public int $totalAmountInCents,
        public OrderSummaryStatus $status,
        public \DateTimeImmutable $placedAt,
        public ?\DateTimeImmutable $cancelledAt,
        public ?int $paymentAmountInCents,
        public ?string $paymentReference,
        public ?string $paymentCheckoutUrl,
        public ?\DateTimeImmutable $paidAt,
        public ?string $trackingReference,
        public ?\DateTimeImmutable $dispatchedAt,
        public ?\DateTimeImmutable $deliveredAt,
    ) {
    }
}
