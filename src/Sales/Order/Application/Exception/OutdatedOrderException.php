<?php

declare(strict_types=1);

namespace Sales\Order\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class OutdatedOrderException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forId(string $productId): self
    {
        return new self(
            message: \sprintf('Product with ID "%s" is no longer available at the claimed price.', $productId),
        );
    }
}
