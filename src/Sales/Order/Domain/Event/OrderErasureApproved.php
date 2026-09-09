<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.order.order.erasure_approved')]
final readonly class OrderErasureApproved
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $approvedAt,
    ) {
    }
}
