<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Finder\PlacedOrder;

use Finance\Payment\Application\Finder\PlacedOrder\Exception\PlacedOrderResultNotFoundException;

interface PlacedOrderFinderInterface
{
    /**
     * @throws PlacedOrderResultNotFoundException
     */
    public function ofId(string $orderId): PlacedOrderResult;
}
