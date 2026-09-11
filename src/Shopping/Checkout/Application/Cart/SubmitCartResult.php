<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Cart;

use Shared\Domain\ValueObject\PostalAddress;

final readonly class SubmitCartResult
{
    public function __construct(
        public string $cartId,
        public int $totalAmountInCents,
        public PostalAddress $billingAddress,
    ) {
    }
}
