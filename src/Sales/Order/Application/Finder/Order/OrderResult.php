<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Order;

use Shared\Application\Result\ResultInterface;

final readonly class OrderResult implements ResultInterface
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
