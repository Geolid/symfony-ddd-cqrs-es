<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\Cart\Exception;

use Shared\Application\Finder\Exception\ResultNotFoundException;

final class CartResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Cart "%s" not found.', $id));
    }
}
