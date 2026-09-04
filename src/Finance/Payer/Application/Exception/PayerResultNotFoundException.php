<?php

declare(strict_types=1);

namespace Finance\Payer\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class PayerResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Payer "%s" not found.', $id));
    }
}
