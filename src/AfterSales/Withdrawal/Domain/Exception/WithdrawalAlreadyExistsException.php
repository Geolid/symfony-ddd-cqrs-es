<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Domain\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class WithdrawalAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Withdrawal "%s" already exists.', $id));
    }
}
