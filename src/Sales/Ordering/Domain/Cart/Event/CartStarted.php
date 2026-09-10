<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Cart\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.ordering.cart.started')]
final readonly class CartStarted
{
    public function __construct(
        public string $id,
        public string $shopperId,
        public \DateTimeImmutable $startedAt,
    ) {
    }
}
