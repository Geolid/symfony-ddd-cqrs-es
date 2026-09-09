<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Uniqueness\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class PaymentAlreadyRequestedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forCart(string $cartId, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('A payment has already been requested for cart "%s".', $cartId),
            previous: $previous,
        );
    }
}
