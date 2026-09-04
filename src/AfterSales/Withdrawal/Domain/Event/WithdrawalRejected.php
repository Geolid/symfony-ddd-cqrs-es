<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('after_sales.withdrawal.withdrawal.rejected')]
final readonly class WithdrawalRejected
{
    public function __construct(
        public string $id,
        public string $reason,
        public \DateTimeImmutable $rejectedAt,
    ) {
    }
}
