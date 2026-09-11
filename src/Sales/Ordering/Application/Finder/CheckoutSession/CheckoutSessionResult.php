<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\CheckoutSession;

final readonly class CheckoutSessionResult
{
    public function __construct(
        public string $checkoutSessionId,
        public string $cartId,
        public string $shopperId,
        public PostalAddressResult $shippingAddress,
        public PostalAddressResult $billingAddress,
    ) {
    }
}
