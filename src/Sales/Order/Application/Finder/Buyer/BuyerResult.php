<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Buyer;

use Shared\Application\Result\ResultInterface;
use Shared\Domain\ValueObject\PostalAddress;

final readonly class BuyerResult implements ResultInterface
{
    public function __construct(
        public string $customerId,
        public ?PostalAddress $shippingAddress,
        public ?PostalAddress $billingAddress,
    ) {
    }
}
