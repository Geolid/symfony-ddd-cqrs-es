<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\Order;

use Sales\Ordering\Application\OrderStatus;
use Shared\Application\ErasureStatus;

final readonly class OrderResult
{
    public function __construct(
        public string $id,
        public string $buyerId,
        public string $paymentId,
        public int $totalAmountInCents,
        public OrderStatus $status,
        public \DateTimeImmutable $confirmedAt,
        public ?\DateTimeImmutable $preparedAt,
        public ?\DateTimeImmutable $dispatchedAt,
        public ?\DateTimeImmutable $deliveredAt,
        public ?\DateTimeImmutable $cancelledAt,
        public ?\DateTimeImmutable $failedAt,
        public ErasureStatus $erasureStatus,
    ) {
    }
}
