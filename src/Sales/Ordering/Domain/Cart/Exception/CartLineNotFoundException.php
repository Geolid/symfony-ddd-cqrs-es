<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Cart\Exception;

use Sales\Ordering\Domain\Shared\ValueObject\LineId;

final class CartLineNotFoundException extends \DomainException
{
    public static function forId(LineId $id): self
    {
        return new self(\sprintf('Cart line "%s" not found.', $id->toString()));
    }
}
