<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Application;

enum WithdrawalStatus: string
{
    case REQUESTED = 'requested';
    case RECEIVED = 'received';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
