<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Checkout;

use Shared\Domain\ValueObject\PostalAddress;

final readonly class CheckoutResult
{
    public function __construct(
        public string $cartId,
        public int $totalAmountInCents,
        public PostalAddress $billingAddress,
    ) {
    }
}
