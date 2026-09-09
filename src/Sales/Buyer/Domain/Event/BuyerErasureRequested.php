<?php

declare(strict_types=1);

namespace Sales\Buyer\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.buyer.buyer.erasure_requested')]
final readonly class BuyerErasureRequested
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $requestedAt,
    ) {
    }
}
