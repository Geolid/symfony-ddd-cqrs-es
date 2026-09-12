<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Cart\Exception;

final class CartProductNotFoundException extends \DomainException
{
    public static function forProductId(string $productId): self
    {
        return new self(\sprintf('Cart product "%s" not found.', $productId));
    }
}
