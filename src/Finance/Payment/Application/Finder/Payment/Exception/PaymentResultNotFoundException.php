<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Finder\Payment\Exception;

use Shared\Application\Finder\Exception\ResultNotFoundException;

final class PaymentResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Payment "%s" not found.', $id));
    }

    public static function forReference(string $reference): self
    {
        return new self(\sprintf('Payment referenced "%s" not found.', $reference));
    }
}
