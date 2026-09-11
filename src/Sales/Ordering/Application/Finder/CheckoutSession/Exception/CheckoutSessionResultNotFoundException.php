<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\CheckoutSession\Exception;

use Shared\Application\Finder\Exception\ResultNotFoundException;

final class CheckoutSessionResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Checkout session "%s" not found.', $id));
    }
}
