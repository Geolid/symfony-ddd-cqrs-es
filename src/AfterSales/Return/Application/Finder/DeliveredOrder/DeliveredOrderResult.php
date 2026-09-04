<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Finder\DeliveredOrder;

final readonly class DeliveredOrderResult
{
    public function __construct(
        public string $orderId,
        public string $buyerId,
        public ShippingAddressResult $shippingAddress,
        public \DateTimeImmutable $deliveredAt,
    ) {
    }
}
