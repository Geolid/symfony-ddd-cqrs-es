<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Cart\Exception;

use Shopping\Checkout\Domain\Cart\ValueObject\CartId;

final class CartAlreadyPurchasedException extends \DomainException
{
    public static function forId(CartId $id): self
    {
        return new self(\sprintf('Cart "%s" has already been purchased.', $id->toString()));
    }
}
