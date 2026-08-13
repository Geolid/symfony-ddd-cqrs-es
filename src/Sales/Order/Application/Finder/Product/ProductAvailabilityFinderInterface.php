<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Product;

use Sales\Order\Application\Exception\ProductChangedException;
use Sales\Order\Application\Exception\ProductNotAvailableException;

interface ProductAvailabilityFinderInterface
{
    /**
     * @throws ProductNotAvailableException
     * @throws ProductChangedException
     */
    public function ensureAvailable(string $productId, string $label, int $unitAmountInCents): void;
}
