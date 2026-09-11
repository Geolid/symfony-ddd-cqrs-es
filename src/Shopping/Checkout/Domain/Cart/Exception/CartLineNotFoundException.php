<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Cart\Exception;

use Shopping\Checkout\Domain\Cart\ValueObject\LineId;

final class CartLineNotFoundException extends \DomainException
{
    public static function forId(LineId $id): self
    {
        return new self(\sprintf('Cart line "%s" not found.', $id->toString()));
    }
}
