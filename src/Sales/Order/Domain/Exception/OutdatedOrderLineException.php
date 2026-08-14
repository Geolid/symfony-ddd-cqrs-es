<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Exception;

final class OutdatedOrderLineException extends \DomainException
{
    public static function forId(string $productId): self
    {
        return new self(\sprintf('Product with ID "%s" is no longer available at the claimed price.', $productId));
    }
}
