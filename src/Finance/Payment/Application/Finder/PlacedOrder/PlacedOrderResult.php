<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Finder\PlacedOrder;

final readonly class PlacedOrderResult
{
    public function __construct(
        public string $orderId,
        public int $amountInCents,
        public BillingAddressResult $billingAddress,
        public bool $cancelled,
    ) {
    }
}
