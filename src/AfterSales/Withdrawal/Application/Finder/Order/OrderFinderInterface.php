<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Application\Finder\Order;

use AfterSales\Withdrawal\Application\Exception\OrderResultNotFoundException;

interface OrderFinderInterface
{
    /**
     * @throws OrderResultNotFoundException
     */
    public function ofId(string $orderId): OrderResult;
}
