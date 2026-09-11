<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Requesting\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class PaymentRequestInProgressException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forCheckoutSession(string $checkoutSessionId, ?\Throwable $previous = null): self
    {
        return new self(
            message: \sprintf('A payment request for checkout session "%s" is already in progress.', $checkoutSessionId),
            previous: $previous,
        );
    }
}
