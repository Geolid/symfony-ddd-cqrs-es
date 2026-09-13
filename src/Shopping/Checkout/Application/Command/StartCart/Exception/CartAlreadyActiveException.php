<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\StartCart\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class CartAlreadyActiveException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forCustomer(string $customerId, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('Customer "%s" already has an active cart.', $customerId),
            previous: $previous,
        );
    }
}
