<?php

declare(strict_types=1);

namespace AfterSales\Return\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('after_sales.return.withdrawal.received')]
final readonly class WithdrawalReceived
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $receivedAt,
    ) {
    }
}
