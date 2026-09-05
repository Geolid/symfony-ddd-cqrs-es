<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Finder\RequestedPayment\Exception;

use Shared\Application\Finder\Exception\ResultNotFoundException;

final class RequestedPaymentResultNotFoundException extends ResultNotFoundException
{
    public static function forOrder(string $orderId): self
    {
        return new self(\sprintf('Placed payment for order "%s" not found.', $orderId));
    }
}
