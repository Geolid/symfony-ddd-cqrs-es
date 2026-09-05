<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Finder\PlacedOrder\Exception;

use Shared\Application\Finder\Exception\ResultNotFoundException;

final class PlacedOrderResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Order "%s" not found.', $id));
    }
}
