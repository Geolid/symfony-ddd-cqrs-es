<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Requesting\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class PaymentRequestCurrencyMismatchException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forCheckoutSession(string $checkoutSessionId): self
    {
        return new self(\sprintf('Checkout session "%s" carries lines in more than one currency.', $checkoutSessionId));
    }
}
