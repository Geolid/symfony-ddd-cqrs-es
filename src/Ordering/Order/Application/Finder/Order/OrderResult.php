<?php

declare(strict_types=1);

namespace Ordering\Order\Application\Finder\Order;

final readonly class OrderResult
{
    public function __construct(
        public string $id,
        public string $customerId,
        public int $totalAmountInCents,
        public string $status,
        public \DateTimeImmutable $placedAt,
        public ?\DateTimeImmutable $cancelledAt,
    ) {
    }
}
