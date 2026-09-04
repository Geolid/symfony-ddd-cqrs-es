<?php

declare(strict_types=1);

namespace AfterSales\Return\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('after_sales.return.withdrawal.approved')]
final readonly class WithdrawalApproved
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $approvedAt,
    ) {
    }
}
