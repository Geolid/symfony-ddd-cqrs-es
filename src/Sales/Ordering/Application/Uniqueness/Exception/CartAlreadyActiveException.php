<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Uniqueness\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class CartAlreadyActiveException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forBuyer(string $buyerId, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('Buyer "%s" already has an active cart.', $buyerId),
            previous: $previous,
        );
    }
}
