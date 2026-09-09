<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Cart\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class CartNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Cart "%s" not found.', $id));
    }
}
