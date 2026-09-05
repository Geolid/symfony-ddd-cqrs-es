<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Order\Exception;

use Shared\Application\Finder\Exception\ResultNotFoundException;

final class OrderResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Order "%s" not found.', $id));
    }
}
