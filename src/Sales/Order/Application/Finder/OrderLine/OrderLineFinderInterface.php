<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\OrderLine;

interface OrderLineFinderInterface
{
    /**
     * @return list<OrderLineResult>
     */
    public function allForOrder(string $orderId): array;
}
