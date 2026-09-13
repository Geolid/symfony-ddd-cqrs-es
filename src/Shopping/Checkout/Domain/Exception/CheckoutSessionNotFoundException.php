<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class CheckoutSessionNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Checkout session "%s" not found.', $id));
    }
}
