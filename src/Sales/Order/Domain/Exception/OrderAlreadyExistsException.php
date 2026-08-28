<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class OrderAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Order "%s" already exists.', $id));
    }
}
