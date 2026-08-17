<?php

declare(strict_types=1);

namespace Sales\Order\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class OrderPaymentAlreadyRequestedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forOrderId(string $orderId, ?\Throwable $previous = null): self
    {
        return new self(
            message: \sprintf('The payment for order "%s" has already been requested.', $orderId),
            previous: $previous,
        );
    }
}
