<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Finder\DeliveredOrder;

use AfterSales\Return\Application\Exception\DeliveredOrderResultNotFoundException;

interface DeliveredOrderFinderInterface
{
    /**
     * @throws DeliveredOrderResultNotFoundException
     */
    public function ofId(string $orderId): DeliveredOrderResult;
}
