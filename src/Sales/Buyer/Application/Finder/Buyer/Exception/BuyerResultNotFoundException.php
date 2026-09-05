<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Finder\Buyer\Exception;

use Shared\Application\Finder\Exception\ResultNotFoundException;

final class BuyerResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Buyer "%s" not found.', $id));
    }
}
