<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class PaymentAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Payment "%s" already exists.', $id));
    }
}
