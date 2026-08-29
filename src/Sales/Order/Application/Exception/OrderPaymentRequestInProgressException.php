<?php

declare(strict_types=1);

namespace Sales\Order\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class OrderPaymentRequestInProgressException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forOrder(string $orderId, ?\Throwable $previous = null): self
    {
        return new self(
            message: \sprintf('A payment request for order "%s" is already in progress.', $orderId),
            previous: $previous,
        );
    }
}
