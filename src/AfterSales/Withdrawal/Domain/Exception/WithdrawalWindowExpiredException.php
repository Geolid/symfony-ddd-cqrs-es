<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Domain\Exception;

use AfterSales\Withdrawal\Domain\ValueObject\WithdrawalId;

final class WithdrawalWindowExpiredException extends \DomainException
{
    public static function forId(WithdrawalId $id): self
    {
        return new self(\sprintf('Withdrawal "%s" is outside its eligibility window.', $id->toString()));
    }
}
