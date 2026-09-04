<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Finder\Order;

use AfterSales\Return\Application\Exception\OrderResultNotFoundException;

interface OrderFinderInterface
{
    /**
     * @throws OrderResultNotFoundException
     */
    public function ofId(string $orderId): OrderResult;
}
