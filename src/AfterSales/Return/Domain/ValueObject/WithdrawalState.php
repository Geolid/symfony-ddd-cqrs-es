<?php

declare(strict_types=1);

namespace AfterSales\Return\Domain\ValueObject;

enum WithdrawalState: string
{
    case REQUESTED = 'requested';
    case RECEIVED = 'received';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
