<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class PaymentNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Payment "%s" not found.', $id));
    }
}
