<?php

declare(strict_types=1);

namespace Sales\Buyer\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.buyer.buyer.erased')]
final readonly class BuyerErased
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $erasedAt,
    ) {
    }
}
