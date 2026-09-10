<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Finder\OrderPayment;

interface OrderPaymentFinderInterface
{
    public function ofOrderOrNull(string $orderId): ?OrderPaymentResult;
}
