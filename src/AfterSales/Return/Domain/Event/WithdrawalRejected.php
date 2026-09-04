<?php

declare(strict_types=1);

namespace AfterSales\Return\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('after_sales.return.withdrawal.rejected')]
final readonly class WithdrawalRejected
{
    public function __construct(
        public string $id,
        public string $reason,
        public \DateTimeImmutable $rejectedAt,
    ) {
    }
}
