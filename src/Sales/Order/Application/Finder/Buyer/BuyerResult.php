<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Buyer;

final readonly class BuyerResult
{
    /**
     * @param array{firstName: string, lastName: string, street: string, postalCode: string, city: string}|null $shippingAddress
     * @param array{firstName: string, lastName: string, street: string, postalCode: string, city: string}|null $billingAddress
     */
    public function __construct(
        public string $customerId,
        public ?array $shippingAddress,
        public ?array $billingAddress,
    ) {
    }
}
