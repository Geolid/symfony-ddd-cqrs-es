<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\StartCart\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class CartAlreadyActiveException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forShopper(string $shopperId, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('Shopper "%s" already has an active cart.', $shopperId),
            previous: $previous,
        );
    }
}
