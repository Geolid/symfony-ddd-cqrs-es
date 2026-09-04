<?php

declare(strict_types=1);

namespace AfterSales\Return\Domain\Exception;

use AfterSales\Return\Domain\ValueObject\WithdrawalId;

final class WithdrawalNotReceivedException extends \DomainException
{
    public static function forId(WithdrawalId $id): self
    {
        return new self(\sprintf('Withdrawal "%s" cannot be decided before its goods are received.', $id->toString()));
    }
}
