<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('after_sales.withdrawal.withdrawal.approved')]
final readonly class WithdrawalApproved
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $approvedAt,
    ) {
    }
}
