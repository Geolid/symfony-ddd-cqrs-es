<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\CheckoutSessionOpening;

use Shared\Domain\ValueObject\PostalAddress;
use Shared\Domain\ValueObject\TaxedAmount;

final readonly class OpenedCheckoutSession
{
    public function __construct(
        public string $checkoutSessionId,
        public TaxedAmount $total,
        public PostalAddress $shippingAddress,
        public PostalAddress $billingAddress,
        public \DateTimeImmutable $expiresAt,
    ) {
    }
}
