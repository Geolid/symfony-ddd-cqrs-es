<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\RequestPayment\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class PaymentAlreadyClaimedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forCheckoutSession(string $checkoutSessionId, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('A payment has already been requested for checkout session "%s".', $checkoutSessionId),
            previous: $previous,
        );
    }
}
