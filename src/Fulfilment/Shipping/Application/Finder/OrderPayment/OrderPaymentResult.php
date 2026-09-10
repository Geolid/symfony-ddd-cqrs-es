<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Finder\OrderPayment;

final readonly class OrderPaymentResult
{
    public function __construct(
        public string $orderId,
        public bool $paid,
    ) {
    }
}
