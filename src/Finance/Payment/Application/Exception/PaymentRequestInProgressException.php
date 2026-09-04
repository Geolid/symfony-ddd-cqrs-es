<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class PaymentRequestInProgressException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forOrder(string $orderId, ?\Throwable $previous = null): self
    {
        return new self(
            message: \sprintf('A payment request for order "%s" is already in progress.', $orderId),
            previous: $previous,
        );
    }
}
