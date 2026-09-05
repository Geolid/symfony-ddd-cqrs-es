<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class RefundResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Refund "%s" not found.', $id));
    }
}
