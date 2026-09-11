<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Cart\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('shopping.checkout.cart.checked_out')]
final readonly class CartCheckedOut
{
    public function __construct(
        public string $id,
        public string $shopperId,
        public int $totalAmountInCents,
        public \DateTimeImmutable $checkedOutAt,
    ) {
    }
}
