<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class ShopperNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Shopper "%s" not found.', $id));
    }
}
