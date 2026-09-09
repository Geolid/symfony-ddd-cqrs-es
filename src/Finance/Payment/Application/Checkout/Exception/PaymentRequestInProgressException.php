<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Checkout\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class PaymentRequestInProgressException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forCart(string $cartId, ?\Throwable $previous = null): self
    {
        return new self(
            message: \sprintf('A payment request for cart "%s" is already in progress.', $cartId),
            previous: $previous,
        );
    }
}
