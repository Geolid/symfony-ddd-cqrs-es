<?php

declare(strict_types=1);

namespace Sales\Order\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class OrderPaymentAlreadyRequestedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forOrderId(string $orderId): self
    {
        return new self(\sprintf('A payment has already been requested for order "%s".', $orderId));
    }
}
