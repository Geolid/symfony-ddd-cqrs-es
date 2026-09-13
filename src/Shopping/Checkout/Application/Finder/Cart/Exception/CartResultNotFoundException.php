<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\Cart\Exception;

use Shared\Application\Finder\Exception\ResultNotFoundException;

final class CartResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('No cart found for id "%s".', $id));
    }
}
