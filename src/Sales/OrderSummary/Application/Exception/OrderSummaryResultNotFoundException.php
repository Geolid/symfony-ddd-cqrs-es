<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class OrderSummaryResultNotFoundException extends ResultNotFoundException
{
    public static function forOrder(string $orderId): self
    {
        return new self(\sprintf('Order summary of order "%s" not found.', $orderId));
    }
}
