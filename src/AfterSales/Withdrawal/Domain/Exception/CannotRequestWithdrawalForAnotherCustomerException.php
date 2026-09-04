<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Domain\Exception;

use AfterSales\Withdrawal\Domain\ValueObject\WithdrawalId;

final class CannotRequestWithdrawalForAnotherCustomerException extends \DomainException
{
    public static function forId(WithdrawalId $id): self
    {
        return new self(\sprintf('Cannot request withdrawal "%s" for another customer.', $id->toString()));
    }
}
