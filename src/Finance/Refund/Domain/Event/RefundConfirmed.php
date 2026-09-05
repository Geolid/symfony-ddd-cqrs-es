<?php

declare(strict_types=1);

namespace Finance\Refund\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('finance.refund.refund.confirmed')]
final readonly class RefundConfirmed
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $refundedAt,
    ) {
    }
}
