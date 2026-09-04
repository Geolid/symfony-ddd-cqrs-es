<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Application\Finder\Order;

final readonly class OrderResult
{
    public function __construct(
        public string $orderId,
        public string $customerId,
        public ShippingAddressResult $shippingAddress,
        public \DateTimeImmutable $deliveredAt,
    ) {
    }
}
