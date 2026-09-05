<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Finder\Withdrawal;

use AfterSales\Return\Application\WithdrawalStatus;

final readonly class WithdrawalResult
{
    public function __construct(
        public string $id,
        public string $orderId,
        public string $buyerId,
        public WithdrawalStatus $status,
        public \DateTimeImmutable $requestedAt,
        public ?\DateTimeImmutable $receivedAt,
        public ?\DateTimeImmutable $approvedAt,
        public ?\DateTimeImmutable $rejectedAt,
    ) {
    }
}
