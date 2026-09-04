<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Domain\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class WithdrawalNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Withdrawal "%s" not found.', $id));
    }
}
