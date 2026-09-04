<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class OrderResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Order "%s" not found.', $id));
    }
}
