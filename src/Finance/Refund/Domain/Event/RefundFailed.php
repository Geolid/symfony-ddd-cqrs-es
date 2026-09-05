<?php

declare(strict_types=1);

namespace Finance\Refund\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('finance.refund.refund.failed')]
final readonly class RefundFailed
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $failedAt,
    ) {
    }
}
