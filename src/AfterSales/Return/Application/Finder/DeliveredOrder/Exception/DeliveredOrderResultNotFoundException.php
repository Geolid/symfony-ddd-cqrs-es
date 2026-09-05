<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Finder\DeliveredOrder\Exception;

use Shared\Application\Finder\Exception\ResultNotFoundException;

final class DeliveredOrderResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Order "%s" not found.', $id));
    }
}
