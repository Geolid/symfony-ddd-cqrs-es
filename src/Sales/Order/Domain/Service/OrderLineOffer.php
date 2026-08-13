<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Service;

use Sales\Order\Domain\Exception\OutdatedOrderLineException;
use Sales\Order\Domain\ValueObject\Product;

final class OrderLineOffer
{
    /**
     * @throws OutdatedOrderLineException
     */
    public function ensureStillValid(Product $claimed, ?Product $current): void
    {
        if (null === $current || !$claimed->price->equals($current->price)) {
            throw OutdatedOrderLineException::forId($claimed->id);
        }
    }
}
