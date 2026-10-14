<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\CheckoutSession;

use Shopping\Checkout\Application\CheckoutSessionStatus;
use Shopping\Checkout\Application\Finder\Customer\PostalAddressResult;

final readonly class CheckoutSessionResult
{
    /**
     * @param list<CheckoutSessionItemResult> $items
     */
    public function __construct(
        public string $id,
        public string $cartId,
        public string $customerId,
        public array $items,
        public PostalAddressResult $shippingAddress,
        public PostalAddressResult $billingAddress,
        public int $totalExcludingTaxInCents,
        public int $totalTaxAmountInCents,
        public int $totalIncludingTaxInCents,
        public string $currency,
        public int $taxRateBasisPoints,
        public CheckoutSessionStatus $status,
        public \DateTimeImmutable $openedAt,
    ) {
    }
}
