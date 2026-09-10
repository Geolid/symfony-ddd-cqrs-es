<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class ShopperAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Shopper "%s" already exists.', $id));
    }
}
