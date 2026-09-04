<?php

declare(strict_types=1);

namespace Finance\Payer\Domain\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class PayerAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Payer "%s" already exists.', $id));
    }
}
