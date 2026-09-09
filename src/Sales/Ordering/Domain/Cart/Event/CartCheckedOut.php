<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Cart\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.ordering.cart.checked_out')]
final readonly class CartCheckedOut
{
    public function __construct(
        public string $id,
        public string $buyerId,
        public int $totalAmountInCents,
        public \DateTimeImmutable $checkedOutAt,
    ) {
    }
}
