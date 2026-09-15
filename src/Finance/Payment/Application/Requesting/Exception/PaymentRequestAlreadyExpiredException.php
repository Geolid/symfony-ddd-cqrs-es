<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Requesting\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class PaymentRequestAlreadyExpiredException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forCheckoutSession(string $checkoutSessionId): self
    {
        return new self(\sprintf('Checkout session "%s" carries an expiration date that is already past.', $checkoutSessionId));
    }
}
