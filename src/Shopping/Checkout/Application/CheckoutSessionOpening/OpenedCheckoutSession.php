<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\CheckoutSessionOpening;

use Shared\Domain\ValueObject\PostalAddress;

final readonly class OpenedCheckoutSession
{
    public function __construct(
        public string $checkoutSessionId,
        public int $totalAmountInCents,
        public PostalAddress $shippingAddress,
        public PostalAddress $billingAddress,
        public \DateTimeImmutable $expiresAt,
    ) {
    }
}
