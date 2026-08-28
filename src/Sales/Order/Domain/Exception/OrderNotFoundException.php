<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class OrderNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Order "%s" not found.', $id));
    }
}
