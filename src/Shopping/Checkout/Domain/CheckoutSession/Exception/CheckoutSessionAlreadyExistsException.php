<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\CheckoutSession\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class CheckoutSessionAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Checkout session "%s" already exists.', $id));
    }
}
