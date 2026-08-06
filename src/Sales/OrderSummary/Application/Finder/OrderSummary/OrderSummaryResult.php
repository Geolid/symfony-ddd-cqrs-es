<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Finder\OrderSummary;

use Sales\OrderSummary\Application\Enum\AppOrderSummaryStatus;
use Shared\Application\Result\ResultInterface;

final readonly class OrderSummaryResult implements ResultInterface
{
    public function __construct(
        public string $orderId,
        public string $customerId,
        public int $totalAmountInCents,
        public AppOrderSummaryStatus $status,
        public \DateTimeImmutable $placedAt,
        public ?\DateTimeImmutable $cancelledAt,
        public ?string $paymentStatus,
        public ?int $paymentAmountInCents,
        public ?string $paymentReference,
        public ?string $paymentCheckoutUrl,
        public ?\DateTimeImmutable $paidAt,
        public ?string $shipmentStatus,
        public ?string $trackingReference,
        public ?\DateTimeImmutable $dispatchedAt,
        public ?\DateTimeImmutable $deliveredAt,
    ) {
    }
}
