<?php

declare(strict_types=1);

namespace Finance\Refund\Domain\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class RefundAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Refund "%s" already exists.', $id));
    }
}
