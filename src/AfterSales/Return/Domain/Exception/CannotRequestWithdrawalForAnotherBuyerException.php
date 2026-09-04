<?php

declare(strict_types=1);

namespace AfterSales\Return\Domain\Exception;

use AfterSales\Return\Domain\ValueObject\WithdrawalId;

final class CannotRequestWithdrawalForAnotherBuyerException extends \DomainException
{
    public static function forId(WithdrawalId $id): self
    {
        return new self(\sprintf('Cannot request withdrawal "%s" for another buyer.', $id->toString()));
    }
}
