<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\CheckoutSession;

use Shopping\Checkout\Application\CheckoutSessionStatus;
use Shopping\Checkout\Application\Finder\Shopper\PostalAddressResult;

final readonly class CheckoutSessionResult
{
    public function __construct(
        public string $id,
        public string $cartId,
        public string $shopperId,
        public PostalAddressResult $shippingAddress,
        public PostalAddressResult $billingAddress,
        public int $totalAmountInCents,
        public CheckoutSessionStatus $status,
        public \DateTimeImmutable $openedAt,
    ) {
    }
}
