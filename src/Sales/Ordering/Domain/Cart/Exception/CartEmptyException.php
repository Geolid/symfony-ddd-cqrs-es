<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Cart\Exception;

use Sales\Ordering\Domain\Cart\ValueObject\CartId;

final class CartEmptyException extends \DomainException
{
    public static function forId(CartId $id): self
    {
        return new self(\sprintf('Cart "%s" carries no line.', $id->toString()));
    }
}
