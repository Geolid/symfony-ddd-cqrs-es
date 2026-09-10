<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\Shopper\Exception;

use Shared\Application\Finder\Exception\ResultNotFoundException;

final class ShopperResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Shopper "%s" not found.', $id));
    }
}
