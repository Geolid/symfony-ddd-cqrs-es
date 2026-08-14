<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Buyer;

use Shared\Application\Result\ResultInterface;

final readonly class BuyerResult implements ResultInterface
{
    public function __construct(
        public string $customerId,
        public ?string $shippingFirstName,
        public ?string $shippingLastName,
        public ?string $shippingStreet,
        public ?string $shippingPostalCode,
        public ?string $shippingCity,
        public ?string $billingFirstName,
        public ?string $billingLastName,
        public ?string $billingStreet,
        public ?string $billingPostalCode,
        public ?string $billingCity,
    ) {
    }

    public function hasCompletedAddresses(): bool
    {
        return null !== $this->shippingFirstName;
    }
}
