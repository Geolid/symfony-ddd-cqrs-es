<?php

declare(strict_types=1);

namespace Shopping\Cart\Domain\Exception;

use Shopping\Cart\Domain\ValueObject\CartId;

final class CartAlreadyPurchasedException extends \DomainException
{
    public static function forId(CartId $id): self
    {
        return new self(\sprintf('Cart "%s" has already been purchased.', $id->toString()));
    }
}
