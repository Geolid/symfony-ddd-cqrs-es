<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class BuyerResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Buyer "%s" not found.', $id));
    }
}
