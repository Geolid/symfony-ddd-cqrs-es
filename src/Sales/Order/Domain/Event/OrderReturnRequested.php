<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.order.order.return_requested')]
final readonly class OrderReturnRequested
{
    public function __construct(
        public string $id,
        public string $requestedAt,
    ) {
    }
}
