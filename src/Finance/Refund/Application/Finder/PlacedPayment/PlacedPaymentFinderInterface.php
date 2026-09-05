<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Finder\PlacedPayment;

use Finance\Refund\Application\Exception\PlacedPaymentResultNotFoundException;

interface PlacedPaymentFinderInterface
{
    /**
     * @throws PlacedPaymentResultNotFoundException
     */
    public function ofOrder(string $orderId): PlacedPaymentResult;
}
