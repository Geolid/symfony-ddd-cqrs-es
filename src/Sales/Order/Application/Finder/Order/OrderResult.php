<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Order;

use Sales\Order\Application\OrderStatus;

final readonly class OrderResult
{
    public function __construct(
        public string $id,
        public string $customerId,
        public int $totalAmountInCents,
        public OrderStatus $status,
        public \DateTimeImmutable $placedAt,
        public ?\DateTimeImmutable $confirmedAt,
        public ?\DateTimeImmutable $dispatchedAt,
        public ?\DateTimeImmutable $deliveredAt,
        public ?\DateTimeImmutable $completedAt,
        public ?\DateTimeImmutable $cancelledAt,
    ) {
    }
}
