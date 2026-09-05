<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Finder\RequestedPayment;

use Finance\Refund\Application\Exception\RequestedPaymentResultNotFoundException;

interface RequestedPaymentFinderInterface
{
    /**
     * @throws RequestedPaymentResultNotFoundException
     */
    public function ofOrder(string $orderId): RequestedPaymentResult;
}
