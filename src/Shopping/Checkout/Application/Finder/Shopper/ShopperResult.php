<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\Shopper;

use Shared\Application\ErasureStatus;

final readonly class ShopperResult
{
    public function __construct(
        public string $id,
        public string $email,
        public \DateTimeImmutable $registeredAt,
        public ?PostalAddressResult $shippingAddress,
        public ?PostalAddressResult $billingAddress,
        public ErasureStatus $erasureStatus,
    ) {
    }
}
