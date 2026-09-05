<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class RequestedPaymentResultNotFoundException extends ResultNotFoundException
{
    public static function forOrder(string $orderId): self
    {
        return new self(\sprintf('Placed payment for order "%s" not found.', $orderId));
    }
}
